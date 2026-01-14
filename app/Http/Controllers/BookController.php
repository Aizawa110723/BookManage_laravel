<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class BookController extends Controller
{

// 書籍情報を楽天BooksAPIから取得してDBに保存
public function fetchFromRakuten(Request $request)
{
    $keyword = $request->input('keyword', 'React'); // デフォルトはReact
    $applicationId = env('RAKUTEN_APP_ID');         // .envに書く

    $url = "https://app.rakuten.co.jp/services/api/BooksBook/Search/2017404";
    $response = Http::get($url, [
        'format' => 'json',
        'applicationId' => $applicationId,
        'title' => $keyword,
        'hits' => 10,
    ]);

$data = $response->json();

$savedBooks =[];  // 初期化

foreach ($data['Items'] as $Item) {
    $bookData = $Item['Item'];

    // 画像をstorageに保存
    $imagePath = null;
    if(!empty($bookData['mediumImageUrl'])) {
        try {
            $contents = file_get_contents($bookData['mediumImageUrl']);
            $name = basename($bookData['mediumImageUrl']);
            $imagePath = 'books/' . $name;
            Storage::disk('public')->put($imagePath, $contents);
        } catch (\Exception $e) {
            Log::warning("画像保存失敗:" . $bookData['title']);
        }
    }

    $book = Book::updateOrCreate(
        ['isbn' => $bookData['isbn']],
        [
            'title' => $bookData['title'],
            'authors' => $bookData['author'] ?? null,
            'publisher' => $bookData['publisherName'] ?? null,
            'year' => $bookData['salesDate'] ?? null,
            'genre' => $bookData['largeGenreName'] ?? null,
            'image_path' => $imagePath,  // storage パス
            'image_url' => $bookData['mediumImageUrl'] ?? null, // 元URL
        ]
    );

    $savedBooks[] = $book;
}

return response()->json([
    'message' => '楽天BooksAPIから書籍情報を取得・保存しました',
    'books' => $savedBooks
]);

}


    // DBから書籍一覧取得（ページネーション）
    public function index()
    {
        $books = Book::paginate(10);
        return response()->json($books);
    }


    // 書籍検索
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
