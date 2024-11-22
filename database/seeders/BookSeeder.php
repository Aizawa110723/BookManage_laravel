<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Book;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //書籍データ
        $books = [
            ['title' => '私と小鳥と鈴と', 'author' => '金子みすゞ', 'publisher' => '金子出版', 'year' => 1923, 'genre' => '詩'],
            ['title' => '坊っちゃん', 'author' => '夏目漱石', 'publisher' => '夏目出版', 'year' => 1906, 'genre' => '小説'],
            ['title' => '草野心平詩集', 'author' => '草野心平', 'publisher' => '草野出版', 'year' => 1940, 'genre' => '詩'],
            ['title' => '蜘蛛の糸', 'author' => '芥川龍之介', 'publisher' => '芥川出版', 'year' => 1918, 'genre' => '短編小説'],
            ['title' => '人間失格', 'author' => '太宰治', 'publisher' => '太宰出版', 'year' => 1948, 'genre' => '小説'],
            ['title' => 'ノルウェイの森', 'author' => '村上春樹', 'publisher' => '講談社', 'year' => 1987, 'genre' => '小説'],
            ['title' => 'コンビニ人間', 'author' => '村田沙耶香', 'publisher' => '村田出版', 'year' => 2016, 'genre' => '小説'],
            ['title' => '雪国', 'author' => '川端康成', 'publisher' => '川端出版', 'year' => 1956, 'genre' => '小説'],
            ['title' => '1Q84', 'author' => '村上春樹', 'publisher' => '新潮社', 'year' => 2009, 'genre' => '小説'],
            ['title' => '火花', 'author' => '又吉直樹', 'publisher' => '文藝春秋', 'year' => 2015, 'genre' => '小説'],
        ];


        // Bookモデルを使ってデータベースに挿入
        foreach ($books as $book) {
            Book::create($book);
        }
    }
}
