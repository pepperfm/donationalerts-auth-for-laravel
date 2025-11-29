<?php

declare(strict_types=1);

namespace Pepperfm\DonationalertsAuth\Providers;

use Illuminate\Support\ServiceProvider;
use Pepperfm\DonationalertsAuth\DonationalertsAuth;

class DonationalertsAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'donationalerts-auth-provider-for-laravel');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'donationalerts-auth-provider-for-laravel');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/config.php' => config_path('donationalerts-auth-for-laravel.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/donationalerts-auth-provider-for-laravel'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/donationalerts-auth-provider-for-laravel'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/donationalerts-auth-provider-for-laravel'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'donationalerts-auth-provider-for-laravel');

        // Register the main class to use with the facade
        $this->app->bind(
            \Pepperfm\DonationalertsAuth\Contracts\DonationalertsAuthContract::class,
            static fn() => new DonationalertsAuth()
        );
    }
}
