<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Book;
use Illuminate\Support\Facades\Http; // Httpクラスのインポート
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;  // ログを使ってデバッグ


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


        foreach ($books as $book) {
            $response = Http::get(
                "https://www.googleapis.com/books/v1/volumes",
                ['q' => 'intitle:' . urlencode($book['title']) . '+inauthor:' . urlencode($book['author'])]
            );


            if ($response->successful()) {
                $data = $response->json();

                // itemsが存在しているか確認
                if (isset($data['items']) && count($data['items']) > 0) {
                    $bookData = $data['items'][0]['volumeInfo'];

                    // 書籍情報を保存
                    $bookRecord = Book::create([
                        'title' => $bookData['title'],
                        'author' => implode(', ', $bookData['authors']),
                        'description' => $bookData['description'] ?? 'No description available',
                        'published_date' => $bookData['publishedDate'],
                        'google_books_url' => $bookData['infoLink'],
                    ]);

                    // 年 (year) を保存
                    if (isset($bookData['publishedDate'])) {
                        $bookRecord->year = substr($bookData['publishedDate'], 0, 4); // 年を取り出して保存
                    }

                    // ジャンル (genre) を保存
                    if (isset($bookData['categories'])) {
                        $bookRecord->genre = implode(', ', $bookData['categories']); // ジャンルを保存
                    }

                    // 画像の保存処理
                    if (isset($bookData['imageLinks']['thumbnail'])) {
                        $imageUrl = $bookData['imageLinks']['thumbnail'];

                        try {
                            // HTTPリクエストを使って画像を取得
                            $imageContents = Http::get($imageUrl)->body();

                            if ($imageContents) {
                                // ランダムな名前をつけて保存
                                $imageName = Str::random(10) . basename($imageUrl);
                                $path = 'books/images/' . $imageName;

                                // ストレージに保存
                                Storage::disk('public')->put($path, $imageContents);

                                // 画像パスをデータベースに保存
                                $book->image_path = $path;
                                $book->save();

                                // 完全なURLを追加
                                $book->image_url = url("storage/{$book->image_path}");
                                $book->save();
                            }
                        } catch (\Exception $e) {
                            Log::error("Error downloading image for book: {$book['title']} by {$book['author']}", [
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            } else {
                Log::error("Failed to fetch book from API: {$book['title']} by {$book['author']}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        }
    }
}
