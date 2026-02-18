<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // ✅ ESTE ES EL BUENO

class AppServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $menu = app(\App\Services\AccessService::class)->menuFor(auth()->user());
                $view->with('drawerMenu', $menu);
            } else {
                $view->with('drawerMenu', collect());
            }
        });
    }

}
