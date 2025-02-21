<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Closure;  // Closureのインポートを追加

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',  // APIルートはCSRFトークン検証をスキップ
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        
        // CSRFトークンが期限切れの場合にカスタマイズされたエラーハンドリングを追加
        try {
            // 親クラスのhandleメソッドを呼び出す（CSRFトークン検証）
            return parent::handle($request, $next);
        } catch (HttpException $e) {
            // トークン期限切れの場合に419エラーを返す
            if ($e->getStatusCode() === 419) {
                return response()->json(['error' => 'CSRF token expired'], 419);
            }

            // その他のエラーはそのまま処理
            throw $e;
        }
    }
}
