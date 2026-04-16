<?php

namespace Synthora\Gem;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('core.state', function ($app) {
            return new class {
                public function get() { return 'active'; }
            };
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/core.php', 'core'
        );
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/core.php' => config_path('core.php'),
            ], 'core-config');
        }

        $this->app->make('core.state')->get();
    }
}