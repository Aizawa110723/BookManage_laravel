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
            // 文学・評論
            ['title' => 'わたしと小鳥とすずと', 'author' => '金子みすゞ', 'publisher' => '金子出版', 'year' => 1923, 'genre' => '文学・評論'],
            ['title' => '坊っちゃん', 'author' => '夏目漱石', 'publisher' => '夏目出版', 'year' => 1906, 'genre' => '文学・評論'],
            ['title' => '草野心平詩集', 'author' => '草野心平', 'publisher' => '草野出版', 'year' => 1940, 'genre' => '文学・評論'],
            ['title' => '蜘蛛の糸', 'author' => '芥川龍之介', 'publisher' => '芥川出版', 'year' => 1918, 'genre' => '文学・評論'],
            ['title' => '人間失格', 'author' => '太宰治', 'publisher' => '太宰出版', 'year' => 1948, 'genre' => '文学・評論'],
            // ノンフィクション
            ['title' => 'サピエンス全史', 'author' => 'ユヴァル・ノア・ハラリ', 'publisher' => '河出書房新社', 'year' => 2014, 'genre' => 'ノンフィクション'],
            ['title' => '人類の未来', 'author' => 'スティーブン・ホーキング', 'publisher' => 'SBクリエイティブ', 'year' => 2018, 'genre' => 'ノンフィクション'],
            // ビジネス・経済
            ['title' => '金持ち父さん貧乏父さん', 'author' => 'ロバート・キヨサキ', 'publisher' => '筑摩書房', 'year' => 1997, 'genre' => 'ビジネス・経済'],
            ['title' => '7つの習慣', 'author' => 'スティーブン・R・コヴィー', 'publisher' => 'キングベアー出版', 'year' => 1989, 'genre' => 'ビジネス・経済'],

        ];


        // Bookモデルを使ってデータベースに挿入
        foreach ($books as $book) {
            Book::create($book);
        }
    }
}
