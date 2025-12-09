<?php

namespace Infoxchange\MessageMedia;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/messagemedia.php',
            'messagemedia'
        );

        $this->app->singleton('messagemedia', function ($app) {
            return new Client(
                config('messagemedia.api_key'),
                config('messagemedia.api_secret'),
                config('messagemedia.base_url'),
                config('messagemedia.use_hmac', false),
                config('messagemedia.proxy')
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/messagemedia.php' => config_path('messagemedia.php'),
        ], 'config');
    }
}
