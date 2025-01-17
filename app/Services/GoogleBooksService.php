<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;  // キャッシュを使用するために追加
use App\Models\Book;

class GoogleBooksService

{
    const API_REQUEST_LIMIT = 80;  // 警告を出すリクエスト回数

    /**
     * Google Books APIから書籍情報を取得
     *
     * @param string $title 書籍のタイトル
     * @param string $author 書籍の著者
     * @return array|null APIから取得した書籍情報
     */
    public function fetchBooks(string $title, string $author)
    {
        // APIリクエストカウントをキャッシュで追跡
        $requestCount = Cache::get('api_request_count', 0);

        // 80回リクエストが行われたら警告を出す
        if ($requestCount >= self::API_REQUEST_LIMIT) {
            Log::warning('API request limit reached: 80 requests made');
        }

        // リクエストカウントをインクリメントし、キャッシュに保存
        Cache::put('api_request_count', $requestCount + 1, now()->addMinutes(10));  // 10分後にリセット

        // Google Books API にリクエストを送信
        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q' => 'intitle:' . $title . '+inauthor:' . $author,
        ]);

        // リクエストが成功した場合
        if ($response->successful()) {
            $items = $response->json()['items'] ?? [];

            // アイテムが存在すれば最初の書籍情報を返す
            if (!empty($items)) {
                return $items[0];
            }

            // アイテムが空の場合はnullを返す
            return null; // '該当なし'ではなくnullを返すことで、より適切に処理できる
        }

        // APIリクエストが失敗した場合、エラーログを出力
        Log::error('Failed to fetch books from Google API', [
            'title' => $title,
            'author' => $author,
            'status' => $response->status(),
            'response' => $response->body(),
        ]);

        // APIリクエスト失敗時には適切なエラーメッセージを返す
        return 'APIリクエストが失敗しました'; // エラー発生時のメッセージ
    }


    /**
     * 書籍画像をダウンロードしてストレージに保存
     *
     * @param string $imageUrl 画像のURL
     * @return string|string[] エラーがあった場合はエラーメッセージ、成功時は画像URL
     */
    public function downloadAndStoreImage(string $imageUrl)
    {
        try {
            // 画像をダウンロード
            $imageContents = Http::get($imageUrl)->body();

            if (!$imageContents) {
                return '画像が取得できませんでした'; // 画像が取得できなかった場合
            }

            // ランダムなファイル名を生成
            $imageName = Str::random(10) . '.jpg'; // 画像ファイル名（ランダム）
            $path = 'books/images/' . $imageName; // 保存先パス

            // 画像をストレージに保存
            Storage::disk('public')->put($path, $imageContents);

            // 保存した画像のURLを返す
            return url("storage/{$path}");
        } catch (\Exception $e) {
            Log::error('Failed to download image', ['error' => $e->getMessage()]);
            return '画像のダウンロードに失敗しました';
        }
    }

    /**
     * 書籍情報をデータベースに保存
     *
     * @param array $bookData 書籍情報
     * @param string|null $imageUrl 画像URL（オプション）
     * @return Book 保存されたBookインスタンス
     */
    public function saveBook(array $bookData, string $imageUrl = "")
    {
        $book = new Book();

        // 必須項目を設定
        $book->title = $bookData['volumeInfo']['title'] ?? 'No Title';
        $book->author = implode(', ', $bookData['volumeInfo']['authors'] ?? []);

        // オプション項目（存在しない場合はnull）
        $book->publisher = $bookData['volumeInfo']['publisher'] ?? 'No Publisher';
        $book->year = $bookData['volumeInfo']['publishedDate'] ? substr($bookData['volumeInfo']['publishedDate'], 0, 4) : null;
        $book->genre = $bookData['volumeInfo']['categories'][0] ?? 'No Genre';
        $book->description = $bookData['volumeInfo']['description'] ?? 'No Description';
        $book->published_date = $bookData['volumeInfo']['publishedDate'] ?? 'No PublishedDate';
        $book->google_books_url = $bookData['volumeInfo']['infoLink'] ?? 'No GoogleBooks URL';

        // 画像URLが提供されていればそのURLを、なければ'画像なし'を保存
        $book->image_url = $imageUrl ? $imageUrl : '画像なし';


        // 画像URLをDBに保存
        try {
            $book->save();
            return $book;
        } catch (\Exception $e) {
            Log::error('Failed to save book to database', [
                'title' => $book->title,
                'author' => $book->author,
                'error' => $e->getMessage(),
                'bookData' => $bookData,
            ]);
            return '保存に失敗しました'; // 保存失敗時にエラーメッセージを返す
        }

        return $book;
    }
}
