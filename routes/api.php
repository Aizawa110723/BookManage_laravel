<?php

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Storage;


/*
|---------------------------------------------------------------------------
| API Routes
|---------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// トップページ用のルート
Route::get('/', function () {
    return response()->json(['message' => 'ようこそ、APIトップページです']);
});

Route::get('/', [BookController::class, 'index']);

// 本の一覧取得（GET）
Route::get('/books', [BookController::class, 'index']);

// 本の登録（POST）
Route::post('/books', [BookController::class, 'store']);

// 本の検索（GET）
// 検索用のルートを /searchbooks に変更
Route::get('/searchbooks', [BookController::class, 'search']);


// Route::middleware('cors')->get('/books', [BookController::class, 'index']);
// Route::middleware('cors')->post('/books', [BookController::class, 'store']);
// Route::get('/', [BookController::class, 'index'])->middleware('cors');



// cors ミドルウェアが指定されているルートに対してのみ CORS ヘッダーが適用
// 画像アップロード（POST）
// もし画像アップロードをAPI経由で行う場合のルート
Route::post('/upload', function (Request $request) {

    // バリデーション（画像ファイルかどうか確認）
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    // 画像ファイルを取得
    $image = $request->file('image');

    // 画像を 'public/images' に保存
    $imagePath = $image->store('public/images');

    // 保存した画像のパスを返す
    return response()->json([
        'message' => '画像が正常にアップロードされました。',
        'image_path' => Storage::url($imagePath), // 画像のURLを返す
    ]);
});
