<?php

namespace AndreiLungeanu\SimpleCart\Listeners;

use AndreiLungeanu\SimpleCart\Events\CartCleared;
use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use Illuminate\Events\Dispatcher;

class CartEventSubscriber
{
    public function handleCartCreated(CartCreated $event): void {}

    public function handleCartUpdated(CartUpdated $event): void {}

    public function handleCartCleared(CartCleared $event): void {}

    public function subscribe(Dispatcher $events): array
    {
        return [
            CartCreated::class => 'handleCartCreated',
            CartUpdated::class => 'handleCartUpdated',
            CartCleared::class => 'handleCartCleared',
        ];
    }
}
