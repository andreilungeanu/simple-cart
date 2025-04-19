<?php

namespace AndreiLungeanu\SimpleCart\Listeners;

use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Events\CartCleared;
use Illuminate\Events\Dispatcher;

class CartEventSubscriber
{
    public function handleCartCreated(CartCreated $event): void
    {
        // Handle cart creation, maybe log it or notify
    }

    public function handleCartUpdated(CartUpdated $event): void
    {
        // Handle cart updates, maybe check inventory
    }

    public function handleCartCleared(CartCleared $event): void
    {
        // Handle cart clearing, maybe cleanup
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            CartCreated::class => 'handleCartCreated',
            CartUpdated::class => 'handleCartUpdated',
            CartCleared::class => 'handleCartCleared',
        ];
    }
}
