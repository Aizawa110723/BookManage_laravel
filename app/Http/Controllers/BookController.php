<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Services\GoogleBooksService;
use App\Services\ImageService; // ImageServiceをインポート
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    protected $googleBooksService;
    protected $imageService;  // ImageServiceのインスタンスを保持

    public function __construct(GoogleBooksService $googleBooksService, ImageService $imageService)
    {
        // コンストラクタでサービスを注入
        $this->googleBooksService = $googleBooksService;
        $this->imageService = $imageService;  // ImageServiceの注入
    }

    public function store(Request $request)
    {
        // バリデーション: リクエストからタイトルと著者を取得
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'authors' => 'required|string|max:255',
        ]);

        $title = $validated['title'];
        $authors = $validated['authors'];

        // キャッシュに保存されている書籍情報を確認
        $cacheKey = 'book_data_' . md5($title . $authors);
        $bookData = Cache::get($cacheKey);

        if (!$bookData) {
            // キャッシュにない場合、Google Books API から書籍情報を取得
            $bookData = $this->googleBooksService->fetchBooks($title, $authors);

            if (!$bookData) {
                return response()->json(['error' => '書籍情報が見つかりませんでした。'], 404);
            }

            // API から取得したデータをキャッシュに保存
            Cache::put($cacheKey, $bookData, now()->addDay()); // 1日間キャッシュ
        }

        // 画像URLがあれば画像をダウンロードして保存
        $imageUrl = $bookData['image_url'] ?? null; // fetchBooksが返す画像URLを取得
        $imagePath = null;

        if ($imageUrl) {
            // ImageServiceを使って画像をダウンロードし、保存
            $imagePath = $this->imageService->downloadAndStoreImage($imageUrl);
        }

        // 書籍情報をDBに保存
        $book = Book::create([
            'title' => $bookData['title'],
            'authors' => implode(', ', $bookData['authors']),
            'publisher' => $bookData['publisher'] ?? 'No Publisher',
            'year' => $bookData['publishedDate'] ?? 'Unknown',
            'genre' => $bookData['categories'][0] ?? 'No Genre',
            'description' => $bookData['description'] ?? 'No Description',
            'image_path' => $imagePath ?? 'No Image',  // 画像パスを保存
        ]);

        // レスポンスを返す
        return response()->json(['message' => '書籍が追加されました！', 'book' => $book], 201);
    }

    public function index()
    {
        // すべての書籍を取得してページネーション
        $books = Book::paginate(10);
        return response()->json($books);
    }

    public function search(Request $request)
    {
        // タイトルと著者をクエリパラメータから取得
        $title = $request->input('title');
        $authors = $request->input('authors');

        // 書籍情報の検索
        $query = Book::query();

        if ($title) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        if ($authors) {
            $query->where('authors', 'like', '%' . $authors . '%');
        }

        // 検索結果を返す
        return response()->json($query->get());
    }
}
