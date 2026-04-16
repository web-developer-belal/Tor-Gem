<?php

namespace Synthora\Gem;

use Illuminate\Console\Command;

class Pw extends Command
{
    protected $signature = 'gem:check';
    protected $description = 'Run system validation';

    public function handle(Rd $rd)
    {
        $rd->v2();
        $this->info('Validation done.');
    }
}