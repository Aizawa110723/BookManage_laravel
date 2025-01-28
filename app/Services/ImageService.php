<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageService
{
    /**
     * 画像をダウンロードしてストレージに保存
     *
     * @param string $imageUrl
     * @return string 画像の保存URLまたはエラーメッセージ
     */
    public function downloadAndStoreImage(string $imageUrl)
    {
        try {
            // 画像をダウンロード
            $response = Http::get($imageUrl);

            // レスポンスのステータスコードをチェック
            if (!$response->successful()) {
                Log::error('画像のダウンロードに失敗', ['url' => $imageUrl, 'status' => $response->status()]);
                return '画像が取得できませんでした';
            }
            // 画像のコンテンツを取得
            $imageContents = $response->body();


            // 画像が取得できなかった場合
            if (!$imageContents) {
                return '画像が取得できませんでした';
            }

            // URLから拡張子を取得
            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);
            if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                $extension = 'jpg'; // 拡張子が不正な場合はデフォルトでjpgを使用
            }

            // ランダムなファイル名を生成して保存先のパスを作成
            $imageName = Str::random(10) . '.' . $extension; // ランダムな画像名と拡張子
            $path = 'books/images/' . $imageName; // 保存パス

            // ストレージに保存
            Storage::disk('public')->put($path, $imageContents);

            // 保存した画像のURLを返す
            return url("storage/{$path}");
        } catch (\Exception $e) {
            // エラーログを記録
            Log::error('画像のダウンロードに失敗しました', [
                'error' => $e->getMessage(),
                'imageUrl' => $imageUrl
            ]);
            return '画像のダウンロードに失敗しました';
        }
    }
}
