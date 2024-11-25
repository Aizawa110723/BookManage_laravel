<?php

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// トップページ用のルート
Route::get('/', function () {
    return response()->json(['message' => 'ようこそ、APIトップページです']);
});

// 本の一覧取得（GET）
Route::get('/books', [BookController::class, 'index']);

// 本の登録（POST）
Route::post('/books', [BookController::class, 'store']);

// 本の検索（GET）
// 検索用のルートを /searchbooks に変更
Route::get('/searchbooks', [BookController::class, 'search']);