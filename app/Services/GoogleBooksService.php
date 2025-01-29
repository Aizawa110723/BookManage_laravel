<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Models\Book; // Bookモデルをインポート

class GoogleBooksService
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * フロントエンドからの書籍情報を基にGoogle Books APIから書籍情報を取得
     *
     * @param array $bookData
     * @return \Illuminate\Http\JsonResponse
     */

    public function fetchBooks(array $bookData)
    {
        // フロントエンドから送られた書籍情報
        $title = urlencode($bookData['title']);
        $authors = urlencode($bookData['authors']);

        // .envからAPIキーを取得
        $apiKey = env('GOOGLE_BOOKS_API_KEY');

        // Google Books APIのリクエストURLにAPIキーを追加
        // maxResults=10で最大10件を取得
        $url = "https://www.googleapis.com/books/v1/volumes?q={$title}+{$authors}&key={$apiKey}&maxResults=10";

        // Google Books APIへリクエスト
        $response = Http::get($url);

        // ステータスコードが429の場合、リクエスト制限に達したと判断
        if ($response->status() == 429) {
            Log::warning('APIリクエスト制限に達しました。', [
                'title' => $bookData['title'],
                'authors' => $bookData['authors'],
                'response' => $response->body(), // レスポンスの内容もログに出力
            ]);
            return Response::json(['message' => 'APIリクエスト制限に達しました。24時間待ってから再試行してください。'], 429);
        }

        // APIリクエスト成功
        if ($response->successful()) {
            $items = $response->json()['items'] ?? [];

            if (empty($items)) {
                return Response::json(['message' => "No books found"], 404);
            }

            $book = $items[0]['volumeInfo'];

            // 言語を確認して日本語書籍かどうかを確認
            $language = $book['language'] ?? 'unknown';

            // 日本語の書籍のみ処理 (ja, ja-JP, jp に対応)
            $validLanguages = ['ja', 'ja-JP', 'jp'];
            if (!in_array($language, $validLanguages)) {
                return Response::json(['message' => 'Only Japanese books are supported'], 400);
            }

            // 出版年とジャンルを取得
            $publishedDate = $book['publishedDate'] ?? 'Unknown';
            $categories = isset($book['categories']) ? implode(', ', $book['categories']) : 'Unknown';

            // 画像URLの取得
            $imageUrl = $book['imageLinks']['thumbnail'] ?? null;

            // 画像URLをログに記録
            Log::info('Image URL:', ['url' => $imageUrl]);

            // 画像URLが存在すればダウンロードして保存
            $imagePath = null;
            if ($imageUrl) {
                $imagePath = $this->imageService->downloadAndStoreImage($imageUrl);
                if ($imagePath === '画像が取得できませんでした' || $imagePath === '画像のダウンロードに失敗しました') {
                    Log::warning('画像のダウンロードに失敗', ['url' => $imageUrl]);
                    $imagePath = null; // 失敗した場合は画像パスをnullに設定
                }
            }

            // 既存の書籍データを確認して重複を防ぐ
            $bookExists = Book::where('title', $book['title'])
                ->where('authors', 'LIKE', '%' . implode('%', $book['authors']) . '%')
                ->exists();

            // 既にデータが存在しない場合にのみ保存
            if (!$bookExists) {
                try {
                    // 書籍をデータベースに保存
                    $bookRecord = Book::create([
                        'title' => $book['title'],
                        'authors' => implode(', ', $book['authors']), // 複数の著者をカンマ区切りで保存
                        'publisher' => $book['publisher'] ?? 'Unknown',
                        'published_date' => $publishedDate, // 出版年を保存
                        'categories' => $categories, // ジャンルを保存
                        'description' => $book['description'] ?? 'No description available.',
                        'infoLink' => $book['infoLink'] ?? null, // Google Booksの該当ページURLを保存
                        'image_path' => $imagePath, // ローカルに保存された画像のパス
                        'image_url' => $imageUrl,    // 画像のURL（外部リンク）
                    ]);

                    Log::info('Book saved successfully', ['book' => $bookRecord]);
                } catch (\Exception $e) {
                    Log::error('Failed to save book to database', [
                        'error' => $e->getMessage(),
                        'bookData' => $book,
                    ]);
                    return Response::json(['message' => 'Failed to save book to database'], 500);
                }
            } else {
                Log::info('Book already exists in the database', ['book' => $book]);
            }
        }


        // APIリクエスト失敗
        Log::error('Failed to fetch books from Google API', [
            'response' => $response->body(),
        ]);
        return Response::json(['message' => 'Failed to fetch books from Google API'], 500);
    }
}
