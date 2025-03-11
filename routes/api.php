<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;


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

// トップページ
Route::get('/', function () {
    return response()->json(['message' => 'ようこそ、APIトップページです']);
});

// 本の登録（POST）にはCORSを適用
Route::post('/books', [BookController::class, 'store'])->middleware(['cors']);

// 本の検索（POST）にもCORSを適用
Route::post('/searchbooks', [BookController::class, 'search'])->middleware(['cors']);

// 本の一覧取得（GET）にもCORSを適用
Route::get('/books', [BookController::class, 'index'])->middleware(['cors']);



// // 作成したミドルウェア'custom.csrf'の適用/ トークンをJSON形式で返す
// Route::get('/get-csrf-token', function () {
//     return response()->json(['csrf_token' => csrf_token()]);  // トークンをJSON形式で返す
// });


// // CSRFトークンを取得するAPIエンドポイント（テスト用）
// Route::get('/get-csrf-token', function () {
//     return response()->json(['csrf_token' => csrf_token()]);
// });


// // web.phpでCSRFトークンのミドルウェアを適用
// Route::middleware('web')->group(function () {
//     Route::post('/books', [BookController::class, 'store']);
// });


// // CSRF保護をAPIにも適用
// Route::middleware(['web', 'csrf'])->post('/books', [BookController::class, 'store']);
