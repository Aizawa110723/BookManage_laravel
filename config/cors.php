<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'], // CORSを適用するパス
    'allowed_methods' => ['*'], // 全てのHTTPメソッドを許可
    'allowed_origins' => ['*'], // 全てのオリジンを許可（開発中のみ）
    'allowed_headers' => ['*'], // 全てのヘッダーを許可
    'allowed_credentials' => false, // クッキー認証が不要な場合はfalse
    'exposed_headers' => [],
    'max_age' => 0,

];
