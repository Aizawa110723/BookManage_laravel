<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Http; // HTTPリクエストを送る
use Illuminate\Support\Facades\Storage;  // ローカルストレージに保存するためのファサード
use Illuminate\Support\Str; // ユニークな文字列を生成する(ファイル名の重複を避ける)

class BookController extends Controller
{
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
        $response = Http::get(
            "https://www.googleapis.com/books/v1/volumes",
            [
                'q' => 'intitle:' . urlencode($title) . '+inauthor:' . urlencode($author)
            ]
        );

        // APIレスポンスの成功を確認
        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to fetch book information from Google Books API'], 500);
        }

        $bookData = $response->json()['items'][0]; // 最初の書籍情報を取得

        // 書籍の基本情報を保存
        $book = new Book();
        $book->title = $bookData['volumeInfo']['title'];
        $book->author = implode(', ', $bookData['volumeInfo']['authors']);
        $book->description = $bookData['volumeInfo']['description'] ?? 'No description available';
        $book->published_date = $bookData['volumeInfo']['publishedDate'];
        $book->google_books_url = $bookData['volumeInfo']['infoLink'];

        // year と genre を保存するための処理を追加
        if (isset($bookData['volumeInfo']['publishedDate'])) {
            $book->year = substr($bookData['volumeInfo']['publishedDate'], 0, 4); // 年を取り出して保存
        }

        if (isset($bookData['volumeInfo']['categories'])) {
            $book->genre = implode(', ', $bookData['volumeInfo']['categories']); // ジャンルを保存
        }

        // 画像のURLを取得してローカルストレージに保存
        if (isset($bookData['volumeInfo']['imageLinks']['thumbnail'])) {
            $imageUrl = $bookData['volumeInfo']['imageLinks']['thumbnail'];

            try {
                // HTTPクライアントを使って画像を取得
                $imageContents = Http::get($imageUrl)->body();

                if (!$imageContents) {
                    return response()->json(['error' => 'Failed to download image: Image URL is invalid'], 500);
                }

                // ファイル名をユニークにするため、ランダムな文字列を付与
                $imageName = Str::random(10) . basename($imageUrl);
                $path = 'books/images/' . $imageName;

                // ストレージに保存
                Storage::disk('public')->put($path, $imageContents);

                // 書籍の画像パスをデータベースに保存する
                $book->image_path = $path;

                // 完全な画像URLを返すためにstorage URLを結合
                $book->image_url = url("storage/{$book->image_path}"); // これで http://localhost:8000/storage/books/images/abc123.jpg のようなURLが生成される
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to download image: ' . $e->getMessage()], 500);
            }
        }

        // DBに書籍情報を保存
        $book->save();

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
