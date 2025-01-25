<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function storeImage(Request $request)
    {
        // バリデーション
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // アップロードされたファイルを取得
        $image = $request->file('image');

        // 'public/images' フォルダに画像を保存
        $imagePath = $image->store('images', 'public');

        // 保存した画像のURLを返す
        return response()->json([
            'message' => 'Image uploaded successfully!',
            'image_url' => Storage::url($imagePath),
        ]);
    }
}
