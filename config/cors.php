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

    'paths' => ['api/*', 'sanctum/csrf-cookie'], // CORSクッキーを適用するパス
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173'],
    'allowed_origins_patterns' => [], //正規表現によるオリジン指定。preg_matchの引数としてそのまま渡される
    'allowed_headers' => ['Content-Type', 'X-XSRF-TOKEN', 'X-Requested-With', 'Authorization', 'Accept'], // 全てのヘッダーを許可

    'exposed_headers' => ['X-XSRF-TOKEN'], //Access-Control-Expose-Headers レスポンスヘッダーの指定

    'max_age' => 0,

    // Access-Control-Allow-Credentialsヘッダーを設定する。
    //falsy値を指定すると出力せず、truthyな値を渡せばtrueが出力される
    'supports_credentials' => true,
];
