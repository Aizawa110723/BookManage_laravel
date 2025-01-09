<?php

namespace Database\Seeders;

use App\Services\GoogleBooksService;
use Illuminate\Database\Seeder;
use App\Models\Book;

class BookSeeder extends Seeder
{
    protected $googleBooksService;

    // コンストラクタでGoogleBooksServiceを注入
    public function __construct(GoogleBooksService $googleBooksService)
    {
        $this->googleBooksService = $googleBooksService;
    }

    public function run()
    {
        // Google Books API から書籍情報を取得
        $booksData = $this->googleBooksService->fetchBooks('laravel', '');  // 例えば「laravel」を検索

        if ($booksData) {
            // 取得した書籍情報をデータベースに保存
            foreach ($booksData['items'] as $item) {
                // 必要な情報を取り出してBookモデルに保存
                Book::create([
                    'title' => $item['volumeInfo']['title'] ?? 'No Title',
                    'author' => implode(', ', $item['volumeInfo']['authors'] ?? []),
                    'publisher' => $item['volumeInfo']['publisher'] ?? null,  // 出版社
                    'year' => isset($item['volumeInfo']['publishedDate']) ? substr($item['volumeInfo']['publishedDate'], 0, 4) : null,  // 出版年（yyyy）
                    'genre' => isset($item['volumeInfo']['categories']) ? implode(', ', $item['volumeInfo']['categories']) : null, // ジャンル
                    'description' => $item['volumeInfo']['description'] ?? null,  // 説明
                    'published_date' => $item['volumeInfo']['publishedDate'] ?? null,  // 出版日
                    'google_books_url' => $item['volumeInfo']['infoLink'] ?? null,  // Google Books のリンク
                    'image_path' => $item['volumeInfo']['imageLinks']['thumbnail'] ?? null,  // 画像のURL
                    'image_url' => $item['volumeInfo']['imageLinks']['thumbnail'] ?? null,  // 保存する画像のURL
                ]);
            }
        }
    }
}
