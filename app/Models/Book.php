<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    // このモデルで使うテーブル
    protected $table = 'books';

    // 複数代入可能な属性
    protected $fillable = [
        'title',
        'author',
        'description',
        'published_date',
        'google_books_url',
        'year',
        'genre',
        'image_path',
        'image_url',
    ];
}
