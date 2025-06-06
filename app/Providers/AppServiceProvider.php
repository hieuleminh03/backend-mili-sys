<?php

namespace App\Providers;

use App\Http\Middleware\CheckAnyRole;
use App\Http\Middleware\CheckRole;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Routing\Router;
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
        // Register middleware in a way that ensures proper binding in the container
        $router = $this->app->make(Router::class);

        // Register the role middleware with explicit binding
        $router->aliasMiddleware('role', CheckRole::class);
        $router->aliasMiddleware('role.any', CheckAnyRole::class);

        // Ensure the middleware classes are properly bound in the container
        $this->app->bind(CheckRole::class, function ($app) {
            return new CheckRole;
        });

        $this->app->bind(CheckAnyRole::class, function ($app) {
            return new CheckAnyRole;
        });

        // Đăng ký observer
        User::observe(UserObserver::class);
    }
}
