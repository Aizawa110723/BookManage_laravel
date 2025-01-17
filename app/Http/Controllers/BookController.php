<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Services\GoogleBooksService;

class BookController extends Controller
{
    protected $googleBooksService;

    // コンストラクタでGoogleBooksServiceを注入
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
        $title = $request->input('title');
        $author = $request->input('author');

        // Google Books APIから書籍情報を取得
        $bookData = $this->googleBooksService->fetchBooks($title, $author);

        // 書籍データが取得できなかった場合
        if (!$bookData) {
            return response()->json(['error' => 'No book found'], 404);
        }

        // 画像のURLを取得して保存
        $imageUrl = null;
        if (isset($bookData['volumeInfo']['imageLinks']['thumbnail'])) {
            $imageUrl = $this->googleBooksService->downloadAndStoreImage($bookData['volumeInfo']['imageLinks']['thumbnail']);
        }

        // 書籍情報をデータベースに保存
        $book = $this->googleBooksService->saveBook($bookData, $imageUrl);

        // 成功レスポンスを返す
        return response()->json(['message' => 'Book added successfully!', 'book' => $book], 201);
    }

    // 書籍リストを取得（ページネーション対応）
    public function index()
    {
        // ページネーションを使用して書籍を取得
        $books = Book::paginate(10); // 1ページあたり10件
        return response()->json($books);
    }


    // 書籍の検索（タイトル・著者で検索）
    public function search(Request $request)
    {
        $title = $request->input('title');
        $author = $request->input('author');

        // Bookモデルを使ってクエリを組み立て
        $query = Book::query();

        // タイトルで検索
        if ($title) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        // 著者で検索
        if ($author) {
            $query->where('author', 'like', '%' . $author . '%');
        }

        // 検索結果を返す
        return response()->json($query->get());
    }
}
