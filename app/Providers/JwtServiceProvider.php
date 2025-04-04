<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\Providers\LaravelServiceProvider;

class JwtServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(LaravelServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure JWT Auth for proper authorization header handling
        config([
            'jwt.blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
            'jwt.ttl' => env('JWT_TTL', 60), // minutes
            'jwt.refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // minutes (14 days)
            'jwt.secret' => env('JWT_SECRET'),
        ]);
    }
}
