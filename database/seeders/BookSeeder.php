<?php

namespace Database\Seeders;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    // 本の登録
    public function run()
    {
        $books = [
            [
                'title' => '電気羊はアンドロイドの夢を見るか？',
                'author' => 'フィリップ・K・ディック',
                'publisher' => '早川書房',
                'year' => 1968,
                'genre' => 'サイエンスフィクション',
                'description' => '未来社会におけるアンドロイドと人間の境界を描いたディストピア小説。',
                'published_date' => '1968-03-01',
                'google_books_url' => 'https://books.google.com/books?id=1VjSAAAAMAAJ',
                'image_path' => 'books/images/sample1.jpg',
                'image_url' => 'http://localhost:8000/storage/books/images/sample1.jpg',
            ],
            [
                'title' => 'コンビニ人間',
                'author' => '村田沙耶香',
                'publisher' => '文藝春秋',
                'year' => 2016,
                'genre' => '文学',
                'description' => '日本社会における異端者として生きるヒロインの心情を描く。',
                'published_date' => '2016-06-15',
                'google_books_url' => 'https://books.google.com/books?id=Zfz2jwEACAAJ',
                'image_path' => 'books/images/sample2.jpg',
                'image_url' => 'http://localhost:8000/storage/books/images/sample2.jpg',
            ],
            [
                'title' => '三毛猫ホームズの推理',
                'author' => '赤川 次郎',
                'publisher' => '双葉社',
                'year' => 1992,
                'genre' => 'ミステリー',
                'description' => '三毛猫ホームズは、名探偵として活躍する猫の姿を描いた物語。人間と猫が協力して事件を解決する。',
                'published_date' => '1992-06-10',
                'google_books_url' => 'https://books.google.com/books?id=xxxxx',
                'image_path' => 'books/images/mikeneko_home.jpg',
                'image_url' => 'http://localhost:8000/storage/books/images/mikeneko_home.jpg',
            ],
            [
                'title' => 'わたしと小鳥と鈴と',
                'author' => '村山 由佳',
                'publisher' => '講談社',
                'year' => 2000,
                'genre' => '恋愛小説',
                'description' => '人と人との関係における心の葛藤と愛を描いた、感動的な恋愛小説。',
                'published_date' => '2000-07-12',
                'google_books_url' => 'https://books.google.com/books?id=yyyyy',
                'image_path' => 'books/images/watashi_to_kotori.jpg',
                'image_url' => 'http://localhost:8000/storage/books/images/watashi_to_kotori.jpg',
            ],
            [
                'title' => '1984年',
                'author' => 'ジョージ・オーウェル',
                'publisher' => 'ハヤカワ文庫',
                'year' => 1950,
                'genre' => 'ディストピア',
                'description' => '未来社会における全体主義体制を描いた名作。',
                'published_date' => '1950-01-01',
                'google_books_url' => 'https://books.google.com/books?id=9XtPAAAAYAAJ',
                'image_path' => 'books/images/sample4.jpg',
                'image_url' => 'http://localhost:8000/storage/books/images/sample4.jpg',
            ],
            [
                'title' => 'ワイルド・スワンズ',
                'author' => 'ユン・チアン',
                'publisher' => '講談社',
                'year' => 1991,
                'genre' => '歴史',
                'description' => '中国近現代の歴史を描いた回顧録。',
                'published_date' => '1991-02-25',
                'google_books_url' => 'https://books.google.com/books?id=vI8FB9I18UwC',
                'image_path' => 'books/images/sample5.jpg',
                'image_url' => 'http://localhost:8000/storage/books/images/sample5.jpg',
            ]
        ];

        foreach ($books as $book) {
            Book::create($book);
        }
    }
}
