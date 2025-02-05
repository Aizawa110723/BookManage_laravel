<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 必要なヘッダーを追加
        return $next($request)
            ->header('Access-Control-Allow-Origin', '*')  // オリジンを許可
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS') // 許可するメソッド
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-XSRF-TOKEN, X-Requested-With, Authorization'); // 許可するヘッダー
    }
}
