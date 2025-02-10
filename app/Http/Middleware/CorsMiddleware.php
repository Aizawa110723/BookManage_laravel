<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = env('CORS_ALLOWED_ORIGINS', '*');

        // OPTIONSリクエストへの対応
        if ($request->getMethod() == 'OPTIONS') {
            return response()->json([], 200)
                ->header('Access-Control-Allow-Origin', $allowedOrigins)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-XSRF-TOKEN, X-Requested-With, Authorization');
        }

        // 次のミドルウェア（またはコントローラ）を実行し、レスポンスを取得
        $response = $next($request);

        // デバッグ用にログを出力して確認
        Log::info('CORS Headers:', $response->headers->all());

        // 必要なヘッダーを追加
        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigins);  // オリジンを許可
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS'); // 許可するメソッド
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-XSRF-TOKEN, X-Requested-With, Authorization'); // 許可するヘッダー
        $response->headers->set('Access-Control-Allow-Credentials', 'true'); // クレデンシャルを許可


        return $response;
    }
}
