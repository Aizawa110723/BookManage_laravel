<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Models\Book;  // Bookモデルをインポート

class GoogleBooksService
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * フロントエンドからの書籍情報を基にGoogle Books APIから書籍情報を取得
     *
     * @param array $bookData
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchBooks(array $bookData)
    {
        // フロントエンドから送られた書籍情報
        $title = urlencode($bookData['title']);
        $authors = urlencode($bookData['authors']);

        // Google Books APIのリクエストURL
        $url = "https://www.googleapis.com/books/v1/volumes?q={$title}+{$authors}";

        // Google Books APIへリクエスト
        $response = Http::get($url);

        // APIリクエスト成功
        if ($response->successful()) {
            $items = $response->json()['items'] ?? [];

            if (empty($items)) {
                return Response::json(['message' => "No books found"], 404);
            }

            $book = $items[0]['volumeInfo'];

            // 画像URLの取得
            $imageUrl = $book['imageLinks']['thumbnail'] ?? null;

            // 画像URLが存在すればダウンロードして保存
            $imagePath = null;
            if ($imageUrl) {
                $imagePath = $this->imageService->downloadAndStoreImage($imageUrl);
                if ($imagePath === '画像が取得できませんでした' || $imagePath === '画像のダウンロードに失敗しました') {
                    Log::warning('画像のダウンロードに失敗', ['url' => $imageUrl]);
                    $imagePath = null;  // 失敗した場合は画像パスをnullに設定
                }
            }

            // 著者名の重複を避けるために既存のデータベースを確認
            foreach ($book['authors'] as $author) {
                // 既に同じ著者が存在していないかチェック
                $existingAuthor = Book::where('authors', 'LIKE', '%' . $author . '%')->first();

                if (!$existingAuthor) {
                    // 存在しない場合は新しく保存
                    Book::create([
                        'title' => $book['title'],
                        'authors' => $author,
                        'publisher' => $book['publisher'] ?? 'Unknown',
                        'published_date' => $book['publishedDate'] ?? 'Unknown',
                        'description' => $book['description'] ?? 'No description available.',
                        'image_url' => $imagePath, // ダウンロードした画像のURL
                    ]);
                }
            }

            // 書籍データを返す
            return Response::json([
                'title' => $book['title'],
                'authors' => $book['authors'] ?? ['Unknown'],
                'publisher' => $book['publisher'] ?? 'Unknown',
                'publishedDate' => $book['publishedDate'] ?? 'Unknown',
                'description' => $book['description'] ?? 'No description available.',
                'image_url' => $imagePath, // ダウンロードした画像のURL
            ]);
        }

        // APIリクエスト失敗
        Log::error('Failed to fetch books from Google API', [
            'response' => $response->body(),
        ]);
        return Response::json(['message' => 'Failed to fetch books from Google API'], 500);
    }
}
