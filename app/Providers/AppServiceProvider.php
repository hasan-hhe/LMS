<?php

namespace App\Providers;

use App\Models\InstanceState;
use App\Models\OrderState;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('admin.*', function ($view) {
            $view->with('instanceStates', InstanceState::all());
            $view->with('orderStates', OrderState::all());
        });
    }
}
