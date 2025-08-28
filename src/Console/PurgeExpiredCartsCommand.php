<?php

namespace AndreiLungeanu\SimpleCart\Console;

use AndreiLungeanu\SimpleCart\Cart\Services\Persistence\DatabaseCartRepository;
use Illuminate\Console\Command;

class PurgeExpiredCartsCommand extends Command
{
    protected $signature = 'cart:purge-expired';

    protected $description = 'Purge expired carts from storage based on configured TTL.';

    public function handle(DatabaseCartRepository $repository): int
    {
        $deleted = $repository->purgeExpired();

        $this->info("Purged {$deleted} expired cart(s).");

        return 0;
    }
}
