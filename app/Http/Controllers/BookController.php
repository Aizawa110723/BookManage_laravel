<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookController extends Controller
{
    /**
     * 楽天BooksAPIで検索（DB保存しない）
     */
    public function fetchFromRakuten(Request $request)
    {

        Log::debug('request all', $request->all());


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
                'isbn' => $book['isbn']
                    ?? $book['isbn13']
                    ?? $book['isbn10']
                    ?? null,
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

        // // フロントから送られてきた内容を丸ごと確認
        // dd($request->all(), $request->json()->all());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'authors' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'year' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    // 許容する形式：YYYY, YYYY年MM月, YYYY年MM月DD日, YYYY年MM月DD日頃
                    if (
                        !preg_match('/^\d{4}年(\d{1,2}月)?(\d{1,2}日)?(頃)?$/', $value) &&
                        !preg_match('/^\d{4}$/', $value)
                    ) {
                        $fail($attribute . ' の形式が正しくありません');
                    }
                }
            ],
            'genre' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'imageUrl' => 'nullable|string',
        ]);

        // デフォルト画像
        $defaultImage = '/images/noprinting.png';


        $data = [
            'title' => $validated['title'] ?: null,
            'authors' => $validated['authors'] ?: null,
            'publisher' => $validated['publisher'] ?: null,
            'year' => $validated['year'] ?: null,
            'genre' => $validated['genre'] ?: null,
            'isbn' => $validated['isbn'] ?: null,
            'image_url' => $validated['imageUrl'] ?: $defaultImage,   // ← 空ならデフォルト画像
            'image_path' => null,
        ];

        // dd($validated);

        //ibsnユニーク判定
        if (!empty($validated['isbn'])) {
            // ISBNがある場合はISBNで判定
            $book = Book::updateOrCreate(['isbn' => $validated['isbn']], $data);
        } else {
            // ISBNがない場合は title+authors+publisher の組み合わせで判定
            $book = Book::updateOrCreate(
                [
                    'title' => $validated['title'],
                    'authors' => $validated['authors'],
                    'publisher' => $validated['publisher'],
                ],
                $data
            );
        }

        return response()->json($book, 201);
    }

    /**
     * 一覧取得
     */
    public function index()
    {
        $books = Book::select('*')
            ->selectRaw("
            (CASE WHEN title IS NOT NULL AND title != '' THEN 1 ELSE 0 END +
             CASE WHEN authors IS NOT NULL AND authors != '' THEN 1 ELSE 0 END +
             CASE WHEN publisher IS NOT NULL AND publisher != '' THEN 1 ELSE 0 END +
             CASE WHEN year IS NOT NULL AND year != '' THEN 1 ELSE 0 END +
             CASE WHEN genre IS NOT NULL AND genre != '' THEN 1 ELSE 0 END +
             CASE WHEN image_url IS NOT NULL AND image_url != '' THEN 1 ELSE 0 END
            ) as completeness
        ")
            ->orderByDesc('completeness') // 完整度が高い順に並べる
            ->limit(10)  // 最大10件
            ->get();  // 実際にDBから取得

        return response()->json($books);
    }

    /**
     * 検索
     */
    public function search(Request $request)
    {

        if (
            !$request->filled('title') &&
            !$request->filled('authors') &&
            !$request->filled('publisher') &&
            !$request->filled('year') &&
            !$request->filled('genre')
        ) {
            return response()->json([], 200);
        }

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
