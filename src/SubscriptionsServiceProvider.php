<?php

namespace Codeassasin\Subscriptions;

use Illuminate\Support\ServiceProvider;

class SubscriptionsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Publish Migrations
         */
        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        /**
         * Push Config File
         */
        $this->publishes([
            __DIR__.'/config/subscription.php' => config_path('subscription.php'),
        ]);

        /**
         * Register Artisan Commands to Renew Subscriptions
         */
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // 
    }
}