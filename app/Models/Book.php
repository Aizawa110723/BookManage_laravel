<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    // このモデルで使うテーブル
    protected $table = 'books';

    // 複数代入可能な属性
    protected $fillable = [
        'title',
        'author',
        'publisher',
        'published_date',
        'categories',
        'description',
        'infoLink',
        'image_path',
        'image_url',
    ];

    // 日付の形式変換
    protected $casts = [
        'published_date' => 'date',
    ];
}
