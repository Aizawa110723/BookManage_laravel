<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    // 本の登録
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'required|string|max:255',
            'year' => 'required|integer',
            'genre' => 'required|string|max:255',
        ]);

        $book = Book::create([
            'title' => $request->title,
            'author' => $request->author,
            'publisher' => $request->publisher,
            'year' => $request->year,
            'genre' => $request->genre,
        ]);

        return response()->json($book, 201);  // 201は作成されたことを示すHTTPステータスコード
    }


    // 本の一覧を取得（GET）
    public function index()
    {
        $books = Book::all();
        return response()->json($books);
    }

    // 本の検索（GET）
    public function search(Request $request)
    {
        $query = $request->input('query');
        $books = Book::where('title', 'like', "%$query%")
            ->orWhere('author', 'like', "%$query%")
            ->get();
        return response()->json($books); // 検索結果を返す
    }
}
