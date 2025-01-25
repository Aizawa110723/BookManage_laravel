<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class GoogleBooksService
{
    /**
     * フロントエンドからの書籍情報を基にGoogle Books APIから書籍情報を取得
     *
     * @param array $bookData
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchBooksFromFrontend(array $bookData)
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

            // 書籍データを返す
            return Response::json([
                'title' => $book['title'],
                'authors' => $book['authors'] ?? ['Unknown'],
                'publisher' => $book['publisher'] ?? 'Unknown',
                'publishedDate' => $book['publishedDate'] ?? 'Unknown',
                'description' => $book['description'] ?? 'No description available.',
                'image_url' => $book['imageLinks']['thumbnail'] ?? null,
            ]);
        }

        // APIリクエスト失敗
        Log::error('Failed to fetch books from Google API', [
            'response' => $response->body(),
        ]);
        return Response::json(['message' => 'Failed to fetch books from Google API'], 500);
    }
}
