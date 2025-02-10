<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Services\GoogleBooksService;
use App\Services\ImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;  // Sessionファサード

class BookController extends Controller
{
    protected $googleBooksService;
    protected $imageService;  // ImageServiceのインスタンスを保持・注入

    public function __construct(GoogleBooksService $googleBooksService, ImageService $imageService)
    {
        // コンストラクタでサービスを注入
        $this->googleBooksService = $googleBooksService;
        $this->imageService = $imageService;  // ImageServiceの注入
    }

    // CSRFトークンを返すアクションを追加
    public function getCsrfToken()
    {
        return response()->json([
            'csrf_token' => csrf_token(),
        ]);
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

        // Google Books API から書籍情報を取得
        $bookData = $this->googleBooksService->fetchBooks([
            'title' => $title,
            'authors' => $authors
        ]);

        // 書籍データが見つからなかった場合
        if (!$bookData) {
            return response()->json(['error' => '書籍情報が見つかりませんでした。'], 404);
        }


        // 画像URLがあれば画像をダウンロードして保存
        $imageUrl = $bookData['imageLinks']['thumbnail'] ?? null; // fetchBooksが返す画像URLを取得
        $imagePath = null;

        if ($imageUrl) {
            // ImageServiceを使って画像をダウンロードし、保存
            $imagePath = $this->imageService->downloadAndStoreImage($imageUrl);
        }

        // 書籍情報をDBに保存
        $book = Book::create([
            'title' => $bookData['title'],
            'authors' => implode(', ', $bookData['authors']),
            'publisher' => $bookData['publisher'] ?? 'Unknown',
            'year' => $bookData['publishedDate'] ?? 'Unknown',
            'genre' => isset($bookData['categories']) ? implode(', ', $bookData['categories']) : 'Unknown',
            'description' => $bookData['description'] ?? 'No description available.',
            'google_books_url' => $googlebooksurl ?? 'No URL',
            'image_path' => $imagePath ?? 'No Image',
            'image_url' => $imageUrl ?? 'No Image URL',
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
