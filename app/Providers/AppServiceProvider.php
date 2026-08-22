<?php

namespace App\Providers;

use App\Listeners\EmbedSystemLogoInMail;
use App\Models\InstanceState;
use App\Models\OrderState;
use App\Notifications\Channels\AblyChannel;
use App\Notifications\Channels\QueuedMailChannel;
use App\Services\AblyService;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AblyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('ably', fn ($app) => $app->make(AblyChannel::class));
        Notification::extend('mail', fn ($app) => $app->make(QueuedMailChannel::class));

        Event::listen(MessageSending::class, EmbedSystemLogoInMail::class);

        View::composer('admin.*', function ($view) {
            $view->with('instanceStates', InstanceState::all());
            $view->with('orderStates', OrderState::all());
        });
    }
}
