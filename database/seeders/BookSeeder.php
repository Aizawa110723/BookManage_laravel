<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Services\GoogleBooksService;


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
        $booksData = $this->googleBooksService->fetchBooks('わたしと小鳥とすずと', '金子みすゞ');  // 例えば検索

        // dd($booksData);

        // '該当なし' の場合は処理を停止
        if (
            $booksData === '該当なし'
        ) {
            echo "書籍情報が見つかりませんでした";
            return;
        }

        if ($booksData) {
            // 取得した書籍情報をデータベースに保存
            foreach ($booksData['items'] as $item) {

                // 必要な情報を取り出してBookモデルに保存
                Book::create([
                    'title' => $item['volumeInfo']['title'] ?? 'No Title',
                    'author' => implode(', ', $item['volumeInfo']['authors'] ?? []),
                    'publisher' => $item['volumeInfo']['publisher'] ?? 'No Publisher',  // 出版社
                    'year' => isset($item['volumeInfo']['publishedDate']) ? substr($item['volumeInfo']['publishedDate'], 0, 4) : null,  // 出版年（yyyy）
                    'genre' => isset($item['volumeInfo']['categories']) ? implode(', ', $item['volumeInfo']['categories']) : 'No Genre', // ジャンル
                    'description' => $item['volumeInfo']['description'] ?? 'No Description',  // 説明
                    'published_date' => $item['volumeInfo']['publishedDate'] ?? 'No Published Date',  // 出版日
                    'google_books_url' => $item['volumeInfo']['infoLink'] ?? 'No GoogleBooks URL',  // Google Books のリンク
                    'image_path' => $item['volumeInfo']['imageLinks']['thumbnail'] ?? 'No Image',  // 画像のURL
                    'image_url' => $item['volumeInfo']['imageLinks']['thumbnail'] ?? 'No Image URL',  // 保存する画像のURL
                ]);
            }
        }
    }
}
