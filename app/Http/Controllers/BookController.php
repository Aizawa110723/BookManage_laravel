<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Http; // HTTPリクエストを送る
use Illuminate\Support\Facades\Storage;  // ローカルストレージに保存するためのファサード


class BookController extends Controller
{
    // 本の登録
    public function store(Request $request)
    {
        // バリデーション（ユーザーから送信されたデータの確認）

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

        if ($response->successful()) {
            $bookData = $response->json()['items'][0]; // 最初の書籍情報を取得

            // 書籍の基本情報を保存
            $book = new Book();
            $book->title = $bookData['volumeInfo']['title'];
            $book->author = implode(', ', $bookData['volumeInfo']['authors']);
            $book->description = $bookData['volumeInfo']['description'] ?? 'No description available';
            $book->published_date = $bookData['volumeInfo']['publishedDate'];
            $book->google_books_url = $bookData['volumeInfo']['infoLink'];

            // 画像のURLを取得してローカルストレージに保存
            if (isset($bookData['volumeInfo']['imageLinks']['thumbnail'])) {

                 // 画像のURLから内容を取得
                //  volumeInfo:タイトルや著者、出版社、出版日など、書籍に関する基本的な情報が入っているキー
                // imageLinks:書籍の画像（サムネイル画像）に関するリンク
                // thumbnail:サムネイル画像のURL
                $imageUrl = $bookData['volumeInfo']['imageLinks']['thumbnail'];

                // 画像ファイルの内容を取得
                $imageContents = file_get_contents($imageUrl);
                $imageName = basename($imageUrl);
                $path = 'books/images/' . $imageName;

                // ストレージに保存
                Storage::disk('public')->put($path, $imageContents);

                // 書籍の画像パスをデータベースのカラム（image_path）に保存する
                $book->image_path = $path;
            }

            // DBに書籍情報を保存
            $book->save();

            return response()->json(['message' => 'Book added successfully!', 'book' => $book], 201);
        }

        return response()->json(['error' => 'Failed to fetch book information from Google Books API'], 500);
    }

    // 書籍リストを取得
    public function index()
    {
        $books = Book::all(); // すべての書籍を取得
        return response()->json($books);
    }
}
