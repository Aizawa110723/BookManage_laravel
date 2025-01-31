<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Book;

class GoogleBooksService
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Google Books APIから書籍データを取得してデータベースに保存
     *
     * @param array $bookData
     * @return void
     */
    public function fetchBooks(array $bookData)
    {
        // Google Books APIに送るパラメータの整形
        $title = urlencode($bookData['title']);
        $authors = urlencode($bookData['authors']);
        // publisherが存在する場合のみ処理
        $publisher = isset($bookData['publisher']) ? urlencode($bookData['publisher']) : '出版社情報なし';
        $publishedDate = $bookData['publishedDate'] ?? '出版日なし';
        $categories = urlencode($bookData['genre']) ? urlencode($bookData['genre']) : 'ジャンルなし';

        // .envからAPIキーを取得
        $apiKey = env('GOOGLE_BOOKS_API_KEY');

        // publisherがある場合、クエリに含める
        $query = "{$title}+{$authors}+{$categories}";
        if ($publisher) {
            $query .= "+{$publisher}";
        }

        // Google Books APIのリクエストURLにAPIキーを追加
        $url = "https://www.googleapis.com/books/v1/volumes?q={$query}&key={$apiKey}";

        // Google Books APIへリクエスト
        $response = Http::get($url);

        if ($response->successful()) {

            // APIからのレスポンスがあれば最初の1冊の情報を取り出す
            $items = $response->json()['items'] ?? [];

            // 書籍が見つからなかった場合
            if (empty($items)) {
                Log::info("No books found for: {$bookData['title']}");
                return null;
            }

            // ここではフロントから送られた情報に基づき1冊の情報を取得
            $book = $items[0]['volumeInfo']; // 最初の本を取得（APIレスポンスが1冊ならこれが唯一の情報）

            // 画像URLの取得
            $imageUrl = $book['imageLinks']['thumbnail'] ?? null;

            // 画像URLが存在すれば画像を保存
            $imagePath = null;
            if ($imageUrl) {

                // 画像保存処理を追加（ImageServiceを使用）
                $imagePath = $this->imageService->downloadAndStoreImage($imageUrl);
                if ($imagePath === '画像のダウンロードに失敗しました') {
                    Log::warning('Failed to download image for book: ' . $bookData['title']);
                    $imagePath = null;
                }
            }

            // データベースに保存
            Book::firstOrCreate(
                ['title' => $book['title'], 'authors' => implode(', ', $book['authors'])], // 重複を避ける
                [
                    'title' => $book['title'],
                    'authors' => implode(', ', $book['authors']),
                    'publisher' => $book['publisher'] ?? 'Unknown',
                    'year' => $book['publishedDate'] ?? 'Unknown',
                    'genre' => isset($book['categories']) ? implode(', ', $book['categories']) : 'Unknown',
                    'description' => $book['description'] ?? 'No description available.',
                    'google_books_url' => $book['infoLink'] ?? 'No URL',
                    'image_path' => $imagePath ?? 'No Image',
                    'image_url' => $imageUrl ?? 'No Image URL',
                ]
            );

            Log::info("Book saved: {$book['title']}");
        } else {
            Log::error('Failed to fetch book data from Google API for ' . $bookData['title']);
            return null; // エラーハンドリング: APIリクエスト失敗
        }
    }
}
