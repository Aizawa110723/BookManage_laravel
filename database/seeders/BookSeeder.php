


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
        // ジャンルのリストを定義
        $genres = [
            'Fiction',       // フィクション（文学・評論）
            'Biography & Memoir', // 自伝・伝記
            'Nonfiction',    // ノンフィクション
            'Fantasy & Science Fiction', // ファンタジー・SF
            'Mystery & Thrillers', // ミステリー・推理
            'Education & Teaching', // 教育・学習
            'Business & Economics', // ビジネス・経済
            'History',       // 歴史・社会
            'Art & Architecture', // アート・建築・デザイン
            'Philosophy',    // 人文・思想・宗教
            'Science & Technology', // 科学・テクノロジー・プログラミング
            'Health & Wellness',    // 健康・ライフスタイル
            'Travel',        // 旅行・ガイド
            'Cooking & Food' // 料理・グルメ
        ];

        // 各ジャンルから1件ずつ書籍情報を取得
        foreach ($genres as $genre) {
            $this->fetchAndSaveBookByGenre($genre);
        }
    }

    /**
     * ジャンルごとに書籍情報を取得して保存
     *
     * @param string $genre ジャンル名
     * @return void
     */
    private function fetchAndSaveBookByGenre($genre)
    {

        // ジャンルの英語名を日本語に変換
        $genreMapping = [
            'Fiction' => '文学・評論',
            'Biography & Memoir' => '自伝・伝記',
            'Nonfiction' => 'ノンフィクション',
            'Fantasy & Science Fiction' => 'ファンタジー・SF',
            'Mystery & Thrillers' => 'ミステリー・推理',
            'Education & Teaching' => '教育・学習',
            'Business & Economics' => 'ビジネス・経済',
            'History' => '歴史・社会',
            'Art & Architecture' => 'アート・建築・デザイン',
            'Philosophy' => '人文・思想・宗教',
            'Science & Technology' => '科学・テクノロジー・プログラミング',
            'Health & Wellness' => '健康・ライフスタイル',
            'Travel' => '旅行・ガイド',
            'Cooking & Food' => '料理・グルメ',
        ];

        $genreJapanese = $genreMapping[$genre] ?? $genre; // 日本語に変換

        // .envからAPIキーを取得
        $apiKey = env('GOOGLE_BOOKS_API_KEY');

        // Google Books APIのリクエストURLにAPIキーを追加
        $url = "https://www.googleapis.com/books/v1/volumes?q=subject:{$genre}&maxResults=1&key={$apiKey}";

        // Google Books APIへリクエスト
        $response = Http::get($url);

        if ($response->successful()) {
            $items = $response->json()['items'] ?? [];

            if (empty($items)) {
                Log::info("No books found for genre: {$genre}");
                return;
            }

            // 取得した書籍情報をDBに保存
            $book = $items[0]['volumeInfo']; // 1件のみ取得

            // 発行年の取得と変換（年だけの場合は01-01に変換）
            $year = $book['publishedDate'] ?? 'Unknown';
            if (preg_match('/^\d{4}$/', $year)) {
                $year = $year . '-01-01'; // 年だけなら、'YYYY-01-01'の形式にする
            }

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
                ['title' => $book['title'], 'authors' => implode(', ', $book['authors'])], // 重複を避ける
                [
                    'title' => $book['title'],
                    'authors' => implode(', ', $book['authors']),
                    'publisher' => $book['publisher'] ?? 'Unknown',
                    'year' => $book['publishedDate'] ?? 'Unknown',
                    'genre' => $genreJapanese, // 日本語のジャンルを保存
                    'description' => $book['description'] ?? 'No description available.',
                    'google_books_url' => $book['infoLink'] ?? 'No URL',
                    'image_path' => $imagePath ?? 'No Image',
                    'image_url' => $imageUrl ?? 'No Image URL',
                ]
            );

            Log::info("Book saved for genre: {$genre} - {$book['title']}");
        } else {
            Log::error('Failed to fetch book data from Google API for genre: ' . $genre);
        }
    }
} 
