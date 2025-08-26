<?php

namespace PDGIOnline\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use PDGIOnline\Auth\Http\Middleware\EnsurePDGIAuth;
use PDGIOnline\Auth\Services\PDGIAuthService;

class PDGIAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/pdgi-auth.php',
            'pdgi-auth'
        );

        $this->app->singleton('pdgi-auth', function () {
            return new PDGIAuthService();
        });
    }

    public function boot(): void
    {
        // config
        $this->publishes([
            __DIR__ . '/../../config/pdgi-auth.php' => config_path('pdgi-auth.php'),
        ], 'pdgi-auth-config');

        // migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        $this->app['router']->aliasMiddleware('pdgi.auth', EnsurePDGIAuth::class);
    }
}
