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

// トップページはCORSなし
Route::get('/', function () {
    return response()->json(['message' => 'ようこそ、APIトップページです']);
});

// 本の登録（POST）にはCORSを適用
Route::post('/books', [BookController::class, 'store'])->middleware('cors');

// 本の一覧取得（GET）にもCORSを適用
Route::get('/books', [BookController::class, 'index'])->middleware('cors');

// 本の検索（GET）にもCORSを適用
Route::get('/searchbooks', [BookController::class, 'search'])->middleware('cors');


// CSRFトークンのエンドポイント
Route::get('/get-csrf-token', [BookController::class, 'getCsrfToken']);



// Route::middleware('cors')->get('/books', [BookController::class, 'index']);
// Route::middleware('cors')->post('/books', [BookController::class, 'store']);
// Route::get('/', [BookController::class, 'index'])->middleware('cors');
