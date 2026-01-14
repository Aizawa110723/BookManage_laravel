<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;


// APIトップ
Route::get('/', function () {
    return response()->json(['message' => 'API top']);
});

// 楽天BooksAPIから取得して保存
Route::get('/books/fetch-rakuten', [BookController::class, 'fetchFromRakuten']);

// 本の登録（手動追加）
Route::post('/books', [BookController::class, 'store']);

// 本の検索
Route::post('/searchbooks', [BookController::class, 'search']);

// 本の一覧取得
Route::get('/books', [BookController::class, 'index']);



