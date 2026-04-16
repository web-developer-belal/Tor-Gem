<?php

namespace Synthora\Gem;

use Closure;

class Vt
{
    public function handle($request, Closure $next)
    {
        app(Rd::class)->v1();
        return $next($request);
    }
}