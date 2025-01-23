<?php

namespace App\Providers;

use App\Services\GoogleBooksService;
use App\Services\ImageService;  // ImageService をインポート
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * アプリケーションのサービスを登録する
     *
     * @return void
     */
    public function register()
    {
        // GoogleBooksServiceをバインドするときにImageServiceもインジェクト
        $this->app->bind(GoogleBooksService::class, function ($app) {
            // ImageServiceを解決して渡す
            return new GoogleBooksService($app->make(ImageService::class));
        });
    }
    /**
     * アプリケーションのサービスをブートする
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
