<?php

namespace AndreiLungeanu\SimpleCart\Commands;

use Illuminate\Console\Command;

class SimpleCartCommand extends Command
{
    public $signature = 'simple-cart';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
