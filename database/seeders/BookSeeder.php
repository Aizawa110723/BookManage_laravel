<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
        // 初回実行フラグをキャッシュから取得
        $firstRun = Cache::get('first_run', true);

        // 初回実行時のみGoogle Books APIから書籍情報を取得
        if ($firstRun) {
            $booksData = $this->googleBooksService->fetchBooks('わたしと小鳥とすずと', '金子みすゞ'); // 例えば検索

            // レスポンス全体をログに出力して確認
            Log::info('Books Data:', ['booksData' => $booksData]);

            // ログにデータを出力して確認
            Log::info('Items:', ['items' => $booksData['items'] ?? 'No Items']);

            // 情報がない場合は処理を停止
            if (!is_array($booksData) || empty($booksData['items'])) {
                echo "書籍情報が見つかりませんでした";
                return;
            }

            // $booksDataに'items'キーが存在するか確認
            if ($booksData && isset($booksData['totalItems']) && $booksData['totalItems'] > 0) {

                // 取得した書籍情報を最大10件までデータベースに保存
                $items = $booksData['items'];
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

                // 初回実行後はフラグを更新して以降の実行を無効にする
                Cache::put('first_run', false);
            } else {

                // itemsが存在しない場合のエラーハンドリング
                Log::error('No books found or totalItems is 0.', ['booksData' => $booksData]);
                echo "書籍情報が見つかりませんでした";
            }
        } else {
            // 初回以外の実行時にはAPI呼び出しを行わない
            echo "APIからのデータ取得は不要です。";
        }
    }
}
