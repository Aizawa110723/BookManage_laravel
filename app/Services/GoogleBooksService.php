<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Book;

class GoogleBooksService

{
    /**
     * Google Books APIから書籍情報を取得
     *
     * @param string $title 書籍のタイトル
     * @param string $author 書籍の著者
     * @return array|null APIから取得した書籍情報
     */
    public function fetchBooks(string $title, string $author)
    {
        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q' => 'intitle:' . urlencode($title) . '+inauthor:' . urlencode($author),
        ]);

        if ($response->successful()) {
            return $response->json()['items'][0] ?? null; // 最初の書籍情報を返す
        }

        // エラーログを出力
        Log::error('Failed to fetch books from Google API', [
            'title' => $title,
            'author' => $author,
            'status' => $response->status(),
            'response' => $response->body(),
        ]);

        return null;
    }

    /**
     * 書籍画像をダウンロードしてストレージに保存
     *
     * @param string $imageUrl 画像のURL
     * @return string|null ストレージに保存された画像のURL
     */
    public function downloadAndStoreImage(string $imageUrl)
    {
        try {
            // 画像をダウンロード
            $imageContents = Http::get($imageUrl)->body();

            if (!$imageContents) {
                return null; // 画像が取得できなかった場合
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
            return null;
        }
    }

    /**
     * 書籍情報をデータベースに保存
     *
     * @param array $bookData 書籍情報
     * @param string|null $imageUrl 画像URL（オプション）
     * @return Book 保存されたBookインスタンス
     */
    public function saveBook(array $bookData, string $imageUrl = null)
    {
        $book = new Book();

        // 必須項目を設定
        $book->title = $bookData['volumeInfo']['title'] ?? 'No Title';
        $book->author = implode(', ', $bookData['volumeInfo']['authors'] ?? []);

        // オプション項目（存在しない場合はnull）
        $book->publisher = $bookData['volumeInfo']['publisher'] ?? null;
        $book->year = $bookData['volumeInfo']['publishedDate'] ? substr($bookData['volumeInfo']['publishedDate'], 0, 4) : null;
        $book->genre = $bookData['volumeInfo']['categories'][0] ?? null;
        $book->description = $bookData['volumeInfo']['description'] ?? null;
        $book->published_date = $bookData['volumeInfo']['publishedDate'] ?? null;
        $book->google_books_url = $bookData['volumeInfo']['infoLink'] ?? null;

        // 画像URLが提供されていれば保存、なければnull
        if ($imageUrl) {
            $book->image_url = $imageUrl;
        }

        // DBに保存
        try {
            $book->save();
        } catch (\Exception $e) {
            Log::error('Failed to save book to database', [
                'title' => $book->title,
                'author' => $book->author,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        return $book;
    }
}
