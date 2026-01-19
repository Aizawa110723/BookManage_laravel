<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Http;

class BookController extends Controller
{
    /**
     * 楽天BooksAPIで検索（DB保存しない）
     */
    public function fetchFromRakuten(Request $request)
    {
        $applicationId = config('services.rakuten.app_id');

        $params = [
            'format' => 'json',
            'applicationId' => $applicationId,
            'hits' => 10,
        ];

        // React → 楽天API 用に変換
        if ($request->filled('title')) {
            $params['title'] = $request->title;
        }
        if ($request->filled('authors')) {
            $params['author'] = $request->authors;
        }
        if ($request->filled('publisher')) {
            $params['publisherName'] = $request->publisher;
        }

        $response = Http::get(
            'https://app.rakuten.co.jp/services/api/BooksBook/Search/20170404',
            $params
        );

        $data = $response->json();

        // 検索結果なし
        if (!isset($data['Items'])) {
            return response()->json([]);
        }

        // React用に整形
        $results = collect($data['Items'])->map(function ($item) {
            $book = $item['Item'];

            return [
                'isbn' => $book['isbn'] ?? null,
                'title' => $book['title'] ?? '',
                'authors' => $book['author'] ?? '',
                'publisher' => $book['publisherName'] ?? '',
                'year' => $book['salesDate'] ?? '',
                'genre' => $book['largeGenreName'] ?? '',
                'imageUrl' => $book['mediumImageUrl'] ?? null,
            ];
        });

        return response()->json($results->values());
    }

    /**
     * 書籍保存（手入力 / 楽天共通）
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'authors' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'year' => 'nullable|string|max:20',
            'genre' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'imageUrl' => 'nullable|string',
        ]);

        $book = Book::updateOrCreate(
            ['isbn' => $validated['isbn']],
            [
                'title' => $validated['title'],
                'authors' => $validated['authors'],
                'publisher' => $validated['publisher'],
                'year' => $validated['year'],
                'genre' => $validated['genre'],
                'image_url' => $validated['imageUrl'],
                'image_path' => null,
            ]
        );

        return response()->json($book, 201);
    }

    /**
     * 一覧取得
     */
    public function index()
    {
        return response()->json(Book::paginate(10));
    }

    /**
     * 検索
     */
    public function search(Request $request)
    {
        $query = Book::query();

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }
        if ($request->filled('authors')) {
            $query->where('authors', 'like', '%' . $request->authors . '%');
        }
        if ($request->filled('publisher')) {
            $query->where('publisher', 'like', '%' . $request->publisher . '%');
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('genre')) {
            $query->where('genre', 'like', '%' . $request->genre . '%');
        }

        return response()->json($query->get());
    }
}
