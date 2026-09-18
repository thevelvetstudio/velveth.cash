<?php

namespace App\Providers;

use App\Contracts\OcrProviderInterface;
use App\Services\Ocr\OcrSpaceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OcrProviderInterface::class, OcrSpaceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
