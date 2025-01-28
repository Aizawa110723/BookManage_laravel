<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Book;

class BookSeeder extends Seeder
{
    public function run()
    {
        // 取り扱いたいジャンルの配列
        $genres = [
            'literature',  // 文学
            'art',         // アート
            'history',     // 歴史
            'science',     // 科学
        ];

        // ランダムにジャンルを選ぶ
        $randomGenre = $genres[array_rand($genres)];

        // Open Library APIのURLをジャンルに基づいて設定
        $url = "https://openlibrary.org/subjects/{$randomGenre}.json";

        // Open Library APIを使って書籍データを取得する
        $response = Http::get($url);

        if ($response->successful()) {
            $data = $response->json();
            // ISBNリストを取得（例として最初の10件を取得）
            $isbnList = array_slice(array_column($data['works'], 'isbn'), 0, 10);

            // ISBNリストを使ってGoogle Books APIに問い合わせ
            $this->fetchBooksFromGoogle($isbnList);
        } else {
            Log::error("Failed to fetch ISBN list from Open Library API for genre: {$randomGenre}");
        }
    }

    private function fetchBooksFromGoogle(array $isbnList)
    {
        // ISBNリストを使ってGoogle Books APIにリクエストを送る
        $isbnQuery = 'isbn:' . implode(' OR isbn:', $isbnList);
        $url = 'https://www.googleapis.com/books/v1/volumes?q=' . $isbnQuery . '&maxResults=10';
        $response = Http::get($url);

        if ($response->successful()) {
            $booksData = $response->json();

            foreach ($booksData['items'] as $item) {
                // 書籍情報を取得
                $title = $item['volumeInfo']['title'] ?? null;
                $authors = $item['volumeInfo']['authors'] ?? null;

                // titleとauthorsが両方とも存在しない場合はスキップ
                if (!$title || !$authors) {
                    Log::info("Skipping book with missing title or authors.");
                    continue;
                }

                // その他の書籍情報を取得
                $publisher = $item['volumeInfo']['publisher'] ?? 'No Publisher';
                $description = $item['volumeInfo']['description'] ?? 'No Description';
                $imageUrl = $item['volumeInfo']['imageLinks']['thumbnail'] ?? null;
                $imagePath = null;

                // 画像URLがあれば画像をダウンロードして保存
                if ($imageUrl) {
                    // 画像保存処理を追加
                    $imagePath = app('App\Services\ImageService')->downloadAndStoreImage($imageUrl);
                }

                // 書籍データをデータベースに保存
                Book::firstOrCreate(
                    ['title' => $title, 'authors' => implode(', ', $authors)],  // authorsは配列なので文字列に変換
                    [
                        'title' => $title,
                        'authors' => implode(', ', $authors),  // 複数の著者をカンマ区切りで保存
                        'publisher' => $publisher,
                        'description' => $description,
                        'image_path' => $imagePath ?? 'No Image',
                        'image_url' => $imageUrl ?? 'No Image URL',
                    ]
                );
            }
        } else {
            Log::error('Failed to fetch books from Google Books API.');
        }
    }
}
