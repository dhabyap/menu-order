<?php

namespace App\Providers;

use App\Support\StoreBrand;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer(['admin', 'login'], function ($view): void {
            $view->with(app(StoreBrand::class)->current());
        });
    }
}
