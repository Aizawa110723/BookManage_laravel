<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
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
        // 例えば、"わたしと小鳥とすずと" と "金子みすゞ" を検索
        $booksData = $this->googleBooksService->fetchBooks('わたしと小鳥とすずと', '金子みすゞ');

        // レスポンス全体をログに出力して確認
        Log::info('Books Data:', ['booksData' => $booksData]);

        // アイテムがない場合のエラーハンドリング
        if (empty($booksData)) {
            Log::error("書籍情報が見つかりませんでした");
            return;
        }

        // 'items'キーが存在し、かつ空でないかをチェック
        if (isset($booksData['items']) && is_array($booksData['items']) && count($booksData['items']) > 0) {

            $items = $booksData['items'];

            // 取得した書籍情報を最大10件までデータベースに保存
            $items = array_slice($items, 0, 10); // 10件だけ取り出す

            foreach ($items as $item) {

                // 書籍画像URLを取得
                $imageUrl = $item['volumeInfo']['imageLinks']['thumbnail'] ?? null;
                $imagePath = null;

                // 画像URLが存在する場合、画像をダウンロードして保存
                if ($imageUrl) {
                    $imagePath = $this->googleBooksService->downloadAndStoreImage($imageUrl);
                }

                // 必要な情報を取り出してBookモデルに保存
                Book::create([
                    'title' => $item['volumeInfo']['title'] ?? 'No Title',
                    'authors' => implode(', ', $item['volumeInfo']['authors'] ?? []),
                    'publisher' => $item['volumeInfo']['publisher'] ?? 'No Publisher', // 出版社
                    'year' => isset($item['volumeInfo']['publishedDate']) ? substr($item['volumeInfo']['publishedDate'], 0, 4) : null, // 出版年（yyyy）
                    'genre' => isset($item['volumeInfo']['categories']) ? implode(', ', $item['volumeInfo']['categories']) : 'No Genre', // ジャンル
                    'description' => $item['volumeInfo']['description'] ?? 'No Description', // 説明
                    'published_date' => $item['volumeInfo']['publishedDate'] ?? 'No Published Date', // 出版日
                    'google_books_url' => $item['volumeInfo']['infoLink'] ?? 'No GoogleBooks URL', // Google Books のリンク
                    'image_path' => $imagePath ?? 'No Image', // 保存した画像のパス
                    'image_url' => $imageUrl ?? 'No Image URL', // 保存する画像のURL
                ]);
            }
        } else {
            // itemsが存在しない場合のエラーハンドリング
            Log::error('No books found or totalItems is 0.', ['booksData' => $booksData]);
            Log::info("書籍情報が見つかりませんでした");
        }
    }
    
    //  画像をダウンロードしてストレージに保存するヘルパーメソッド
    protected function downloadAndStoreImage($imageUrl)
    {
        $imageContents = file_get_contents($imageUrl);
        $imageName = basename($imageUrl);
        $imagePath = public_path('images/' . $imageName);
        file_put_contents($imagePath, $imageContents);

        return 'images/' . $imageName;
    }
}
