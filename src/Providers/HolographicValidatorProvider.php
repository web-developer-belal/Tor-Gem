<?php

namespace Synthora\Gem;

use Illuminate\Support\ServiceProvider;

class HolographicValidatorProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('holographic.validator', function ($app) {
            return new class {
                public function validate($data) { return true; }
            };
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/holographic.php', 'holographic'
        );
    }

    public function boot()
    {
        $this->app['validator']->extend('holographic', function ($attribute, $value, $parameters, $validator) {
            return $this->app->make('holographic.validator')->validate($value);
        }, 'The :attribute failed holographic verification.');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/holographic.php' => config_path('holographic.php'),
            ], 'holographic-config');
        }
    }
}