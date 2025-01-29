<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // 空不可
            $table->string('authors'); // 空不可
            $table->string('publisher')->nullable();
            $table->date('published_date')->nullable();
            $table->text('categories')->nullable();
            $table->text('description')->nullable(); // 書籍の説明（オプション）
            $table->text('infoLink')->nullable(); // Google BooksのURL（オプション）
            $table->string('image_path')->nullable();  // 画像のパス（オプション）
            $table->string('image_url')->nullable();   // 画像のURL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
