<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Book;
use App\Services\ImageService;

class BookSeeder extends Seeder
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function run()
    {
        // ランダムに保存されている書籍から title と authors を選ぶ
        $book = Book::inRandomOrder()->first(); // ランダムに書籍1件を取得

        // 書籍が存在する場合のみ処理
        if ($book) {
            $searchQuery = [
                'title' => $book->title,
                'authors' => $book->authors,
            ];

            // Google Books APIから書籍情報を取得して保存
            $this->fetchAndSaveBooksFromGoogle($searchQuery);
        } else {
            Log::warning('No books found in the database to seed.');
        }
    }

    /**
     * Google Books APIから書籍データを取得してデータベースに保存
     *
     * @param array $bookData ['title' => 'タイトル', 'authors' => '著者']
     * @return void
     */
    private function fetchAndSaveBooksFromGoogle(array $bookData)
    {
        // .envからAPIキーを取得
        $apiKey = env('GOOGLE_BOOKS_API_KEY');

        // Google Books APIのリクエストURLにAPIキーを追加
        // title と authors のみを使って検索
        // 最大10件
        $title = urlencode($bookData['title']);
        $authors = urlencode($bookData['authors']);

        $url = "https://www.googleapis.com/books/v1/volumes?q=intitle:{$title}+inauthor:{$authors}&key={$apiKey}&maxResults=10";

        // Google Books APIへリクエスト
        $response = Http::get($url);

        if ($response->successful()) {
            $items = $response->json()['items'] ?? [];

            if (empty($items)) {
                Log::info("No books found for title: {$bookData['title']} and authors: {$bookData['authors']}");
                return;
            }

            // 最大10件の書籍情報をDBに保存
            foreach ($items as $item) {
                $book = $item['volumeInfo'];

                // title と authors が完全一致する場合にのみ処理を進める
                if (
                    stripos($book['title'], $bookData['title']) !== false &&
                    stripos($book['authors'][0], $bookData['authors']) !== false
                ) {

                    // 画像URLの取得
                    $imageUrl = $book['imageLinks']['thumbnail'] ?? null;

                    // 画像URLが存在すれば画像を保存
                    $imagePath = null;
                    if ($imageUrl) {
                        // ImageServiceを使って画像を保存
                        $imagePath = $this->imageService->downloadAndStoreImage($imageUrl);
                        if ($imagePath === '画像のダウンロードに失敗しました') {
                            Log::warning('Failed to download image for book: ' . $book['title']);
                            $imagePath = null;
                        }
                    }

                    // データベースに保存
                    Book::firstOrCreate(
                        ['title' => $book['title'], 'authors' => implode(', ', $book['authors'])],  // 重複を避ける
                        [
                            'title' => $book['title'],
                            'authors' => implode(', ', $book['authors']),
                            'publisher' => $book['publisher'] ?? 'Unknown',
                            'year' => $book['publishedDate'] ?? 'Unknown',
                            'genre' => isset($book['categories']) ? implode(', ', $book['categories']) : 'Unknown',
                            'description' => $book['description'] ?? 'No description available.',
                            'google_books_url' => $googlebooksurl ?? 'No URL',
                            'image_path' => $imagePath ?? 'No Image',
                            'image_url' => $imageUrl ?? 'No Image URL',
                        ]
                    );

                    Log::info("Book saved: {$book['title']}");
                }
            }
        } else {
            Log::error('Failed to fetch book data from Google API for ' . $bookData['title']);
        }
    }
}
