<?php
namespace PDGIOnline\PDGIAuthClient\Providers;

use Illuminate\Support\ServiceProvider;
use PDGIOnline\PDGIAuthClient\Http\Middleware\EnsurePDGIAuth;
use PDGIOnline\PDGIAuthClient\Services\PDGIAuthService;

class PDGIAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/pdgi-auth.php', 'pdgi-auth'
        );

        $this->app->singleton('pdgi-auth', function () {
            return new PDGIAuthService();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/pdgi-auth.php' => config_path('pdgi-auth.php'),
        ], 'pdgi-auth-config');

        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        $this->app['router']->aliasMiddleware('pdgi.auth', EnsurePDGIAuth::class);
    }
}