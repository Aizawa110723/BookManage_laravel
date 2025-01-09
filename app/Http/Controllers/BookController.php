<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\GoogleBooksService;
use Illuminate\Http\Request;

class BookController extends Controller
{
    protected $googleBooksService;

    // コンストラクタでGoogleBooksServiceをインジェクション
    public function __construct(GoogleBooksService $googleBooksService)
    {
        $this->googleBooksService = $googleBooksService;
    }

    // 本の登録
    public function store(Request $request)
    {

        // バリデーション（ユーザーから送信されたデータの確認）
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
        ]);

        // リクエストからタイトルと著者を取得
        $title = 'laravel';
        $author = '';

        // Google Books APIから書籍情報を取得
        $bookData = $this->googleBooksService->fetchBooks($title, $author);

        if (!$bookData) {
            return response()->json(['error' => 'Failed to fetch book information from Google Books API'], 500);
        }

        // 画像のURLを取得して保存
        $imageUrl = null;
        if (isset($bookData['volumeInfo']['imageLinks']['thumbnail'])) {
            $imageUrl = $this->googleBooksService->downloadAndStoreImage($bookData['volumeInfo']['imageLinks']['thumbnail']);
            if (!$imageUrl) {
                return response()->json(['error' => 'Failed to download image: Image URL is invalid'], 500);
            }
        }

        // 書籍情報をデータベースに保存
        $book = $this->googleBooksService->saveBook($bookData, $imageUrl);

        return response()->json(['message' => 'Book added successfully!', 'book' => $book], 201);
    }

    // 書籍リストを取得（ページネーション対応）
    public function index()
    {
        // ページネーションを使用して書籍を取得
        $books = Book::paginate(10); // 1ページあたり10件
        return response()->json($books);
    }
}
