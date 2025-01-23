<?php

namespace App\Services;

use App\Services\ImageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
// use App\Models\Book;

class GoogleBooksService
{
    protected $imageService;
    const API_REQUEST_LIMIT = 80; // 警告を出すリクエスト回数

    // コンストラクタでImageServiceをインジェクト
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Google Books APIから書籍情報を取得
     *
     * @param string $title 書籍のタイトル
     * @param string $authors 書籍の著者
     * @return array|null APIから取得した書籍情報
     */
    public function fetchBooks(string $title, string $authors)
    {
        // 初回実行フラグをキャッシュで取得
        $firstRun = Cache::get('first_run', true);

        // 初回実行時にのみAPIリクエストを送信
        if ($firstRun) {
            // APIリクエストカウントをキャッシュで追跡
            $requestCount = Cache::get('api_request_count', 0);

            // 80回リクエストが行われたら警告を出す
            if ($requestCount >= self::API_REQUEST_LIMIT) {
                Log::warning('API request limit reached: 80 requests made');
            }

            // リクエストカウントをインクリメントし、キャッシュに保存
            Cache::put('api_request_count', $requestCount + 1, now()->addDay()); // 24時間後にリセット

            // Google Books APIのURLに検索語句をパラメータとして付けてリクエスト
            $title = urlencode($title);
            $authors = urlencode($authors);
            $url = 'https://www.googleapis.com/books/v1/volumes?q=' . $title . '+' . $authors;

            // HTTP GETリクエストを送信
            $response = Http::get($url);

            // レスポンスの内容をログに出力
            Log::info('Google Books API response:', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(), // JSONデータもログに出力
            ]);

            // リクエストが成功した場合
            if ($response->successful()) {
                $totalItems = $response->json()['totalItems'] ?? 0;

                // totalItems が 0 の場合
                if ($totalItems == 0) {
                    Log::info("No books found for the query: {$title} by {$authors}");
                    return null;
                }

                $items = $response->json()['items'] ?? [];

                // items が空でないか確認
                if (empty($items)) {
                    Log::info("Items array is empty even though totalItems is {$totalItems}.");
                    return null;
                }

                Log::info("Found books: ", ['books' => $items]);

                $items = $response->json()['items'] ?? null;
                if (empty($items)) {
                    Log::info("No books found for query: {$title} by {$authors}");
                    return null; // 書籍が見つからない場合はnullを返す
                }


                // 最初の書籍情報を取得
                $firstItem = $items[0]['volumeInfo'] ?? null;
                if (!$firstItem) {
                    Log::error("No book data found.");
                    return null;
                }

                Log::info("First book data:", ['firstItem' => $firstItem]);

                // 画像URLを取得し、ImageService を使って画像を保存
                $imageUrl = $firstItem['imageLinks']['thumbnail'] ?? null;

                if ($imageUrl) {
                    // 画像を保存し、そのURLを返す
                    $imagePath = $this->imageService->downloadAndStoreImage($imageUrl);

                    if (!$imagePath) {
                        Log::warning("Image download and store failed for: {$imageUrl}");
                    } else {
                        Log::info("Image successfully saved at: {$imagePath}");
                    }
                } else {
                    Log::info("No image URL found for the book: {$title}");
                }

                // 最初の書籍情報を返す
                return $firstItem;
            }


            // APIリクエストが失敗した場合、エラーログを出力
            Log::error('Failed to fetch books from Google API', [
                'title' => $title,
                'authors' => $authors,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            // APIリクエスト失敗時にはエラーメッセージを返す
            return null; // エラー発生時にnullを返す
        }

        // 初回以降の処理（Google APIからのデータ取得は不要）
        return null; // Google APIからのデータは必要ない
    }


    /**
     * 画像のダウンロードとストレージへの保存
     *
     * @param string $imageUrl 画像のURL
     * @return string|null 保存された画像のURL
     */
    public function downloadAndStoreImage(string $imageUrl)
    {
        try {
            // 画像をダウンロード
            $imageContents = Http::get($imageUrl)->body();

            // 画像が取得できなかった場合のエラーログ
            if (!$imageContents) {
                Log::warning("Failed to download image from URL: {$imageUrl}");
                return null;  // 画像が取得できなかった場合はnullを返す
            }

            // 画像URLから拡張子を取得
            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);

            // 拡張子がない場合はデフォルトで.jpgを設定
            if (!$extension) {
                $extension = 'jpg';
            }

            // ランダムなファイル名を生成
            $imageName = uniqid('book_', true) . '.' . $extension; // 画像ファイル名（ユニーク）
            $path = 'books/images/' . $imageName; // 保存先パス

            // 画像をストレージに保存
            $stored = Storage::disk('public')->put($path, $imageContents);

            // 保存に失敗した場合
            if (!$stored) {
                Log::error("Failed to store image at path: {$path}");
                return null;
            }

            // 保存した画像のURLを返す
            return url("storage/{$path}");
        } catch (\Exception $e) {
            Log::error('Failed to download image', ['error' => $e->getMessage(), 'imageUrl' => $imageUrl]);
            return null;  // エラーが発生した場合はnullを返す
        }
    }
}
