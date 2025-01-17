<?php

namespace App\Providers;

use App\Services\GoogleBooksService;
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
        // GoogleBooksService をバインディング
        $this->app->singleton(GoogleBooksService::class, function ($app) {
            return new GoogleBooksService();
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
