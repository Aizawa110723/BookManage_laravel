<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Services\GoogleBooksService;
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    protected $googleBooksService;

    public function __construct(GoogleBooksService $googleBooksService)
    {
        $this->googleBooksService = $googleBooksService;
    }

    public function store(Request $request)
    {
        // バリデーション
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
            // Google Books API から書籍情報を取得
            $bookData = $this->googleBooksService->fetchBooks($title, $authors);

            if (!$bookData) {
                return response()->json(['error' => '書籍情報が見つかりませんでした。'], 404);
            }

            // API から取得したデータをキャッシュに保存
            Cache::put($cacheKey, $bookData, now()->addDay()); // 1日間キャッシュ
        }

        // 画像URLがあれば画像をダウンロードして保存
        $imageUrl = $bookData['volumeInfo']['imageLinks']['thumbnail'] ?? null;
        $imagePath = null;

        if ($imageUrl) {
            $imagePath = $this->googleBooksService->downloadAndStoreImage($imageUrl);
        }

        // 書籍情報を DB に保存
        $book = Book::create([
            'title' => $bookData['volumeInfo']['title'],
            'authors' => implode(', ', $bookData['volumeInfo']['authors']),
            'publisher' => $bookData['volumeInfo']['publisher'] ?? 'No Publisher',
            'year' => $bookData['volumeInfo']['publishedDate'] ?? 'Unknown',
            'genre' => $bookData['volumeInfo']['categories'][0] ?? 'No Genre',
            'description' => $bookData['volumeInfo']['description'] ?? 'No Description',
            'image_path' => $imagePath ?? 'No Image',
        ]);

        return response()->json(['message' => '書籍が追加されました！', 'book' => $book], 201);
    }

    public function index()
    {
        $books = Book::paginate(10);
        return response()->json($books);
    }

    public function search(Request $request)
    {
        $title = $request->input('title');
        $authors = $request->input('authors');

        $query = Book::query();

        if ($title) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        if ($authors) {
            $query->where('authors', 'like', '%' . $authors . '%');
        }

        return response()->json($query->get());
    }
}
