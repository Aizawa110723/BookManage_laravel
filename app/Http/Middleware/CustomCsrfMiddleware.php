

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomCsrfMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // ヘッダーからCSRFトークンを取得
        $csrfToken = $request->header('X-XSRF-TOKEN');

        Log::info('Received CSRF Token:', ['csrf_token' => $csrfToken]);  // リクエストから受け取ったトークン
        Log::info('Expected CSRF Token:', ['expected_token' => csrf_token()]);  // Laravel が期待しているトークン


        // CSRFトークンが一致するか確認
        if (!$csrfToken || !hash_equals(csrf_token(), $csrfToken)) {
            Log::error('Invalid CSRF Token');
            // トークンが一致しない場合は403エラーを返す
            return response()->json(['error' => '無効なトークンです'], Response::HTTP_FORBIDDEN);
        }

        // トークンが一致する場合、次の処理に進む
        return $next($request);
    }
}
