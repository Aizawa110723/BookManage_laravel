<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomCsrfMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // ヘッダーからCSRFトークンを取得
        $csrfToken = $request->header('X-XSRF-TOKEN');

        // CSRFトークンが一致するか確認
        if (!$csrfToken || !hash_equals(csrf_token(), $csrfToken)) {
            
            // トークンが一致しない場合は403エラーを返す
            return response()->json(['error' => '無効なトークンです'], Response::HTTP_FORBIDDEN);
        }

        // トークンが一致する場合、次の処理に進む
        return $next($request);
    }
}
