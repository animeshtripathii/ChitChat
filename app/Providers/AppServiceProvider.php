<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\SmsServiceInterface::class, function ($app) {
            $driver = config('sms.driver', 'local');

            if ($driver === 'sandbox') {
                return new \App\Services\SandboxSmsService();
            }

            return new \App\Services\LocalSmsService();
        });
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageSent::class,
            \App\Listeners\ModerateMessageListener::class
        );
    }
}
