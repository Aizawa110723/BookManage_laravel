<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;  // ログを使うために追加

class BookSeeder extends Seeder
{
    public function run()
    {
        // 初期データ
        $books = [
            [
                'title' => '電気羊はアンドロイドの夢を見るか？',
                'author' => 'フィリップ・K・ディック',
            ],
            [
                'title' => 'わたしと小鳥とすずと',
                'author' => '金子みすゞ',
            ],
            [
                'title' => '三毛猫ホームズの推理',
                'author' => '赤川次郎',
            ],
            [
                'title' => '蜘蛛の糸',
                'author' => '芥川龍之介',
            ],
            [
                'title' => '竜馬がゆく',
                'author' => '司馬遼太郎',
            ],
        ];

        foreach ($books as $book) {
            Log::info('Processing book: ' . $book['title']); // 処理中の書籍情報をログに出力

            // Google Books APIにリクエストを送る
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.google_books.api_key'), // APIキーを取得
            ])->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => 'intitle:' . urlencode($book['title']) . '+inauthor:' . urlencode($book['author']),
            ]);

            // レスポンスのチェック
            if ($response->successful()) {
                // APIが成功した場合
                $responseData = $response->json();
                // データ処理

                // items 配列が存在し、書籍が1冊以上ある場合
                if (isset($responseData['items'][0])) {
                    $bookData = $responseData['items'][0];

                    // Bookインスタンスを新規作成
                    $bookInstance = new Book();
                    $bookInstance->title = $bookData['volumeInfo']['title'];
                    $bookInstance->author = implode(', ', $bookData['volumeInfo']['authors']);
                    $bookInstance->description = $bookData['volumeInfo']['description'] ?? 'No description available';
                    $bookInstance->published_date = $bookData['volumeInfo']['publishedDate'];
                    $bookInstance->google_books_url = $bookData['volumeInfo']['infoLink'] ?? null;

                    // 画像の保存
                    if (isset($bookData['volumeInfo']['imageLinks']['thumbnail'])) {
                        $imageUrl = $bookData['volumeInfo']['imageLinks']['thumbnail'];
                        try {

                            // 画像URLを使って画像データを取得
                            $imageContents = Http::get($imageUrl)->body();
                            $imageName = Str::random(10) . basename($imageUrl);
                            $path = 'books/images/' . $imageName;
                            Storage::disk('public')->put($path, $imageContents);
                            $bookInstance->image_path = $path;
                            $bookInstance->image_url = url('storage/' . $path);
                            Log::info('Image saved successfully for book: ' . $bookInstance->title);
                        } catch (\Exception $e) {
                            Log::error('Failed to save image for book: ' . $bookInstance->title . '. Error: ' . $e->getMessage());
                        }
                    } else {
                        Log::warning('No image found for book: ' . $bookInstance->title);
                    }

                    // データベースに書籍情報を保存
                    try {
                        $bookInstance->save();
                        Log::info('Book added successfully: ' . $bookInstance->title);
                    } catch (\Exception $e) {
                        Log::error('Failed to save book: ' . $bookInstance->title . '. Error: ' . $e->getMessage());
                    }
                } else {
                    Log::warning('No books found for search query: ' . $book['title']);
                }
            } else {
                Log::error('Failed to fetch book data for: ' . $book['title']);
            }
        }
    }
}

