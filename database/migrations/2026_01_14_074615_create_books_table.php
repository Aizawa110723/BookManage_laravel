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
            $table->id();                             // 自動増分ID
            $table->string('isbn', 20)->unique();     // ISBN（ユニーク制約）
            $table->string('title');                  // 書名
            $table->string('authors')->nullable();    // 著者
            $table->string('publisher')->nullable();  // 出版社
            $table->string('year', 20)->nullable();   // 発売日
            $table->string('genre')->nullable();      // ジャンル
            $table->string('image_path')->nullable(); // storage 内の画像パス
            $table->string('image_url')->nullable();  // 元画像の URL
            $table->timestamps();                     // created_at / updated_at
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
