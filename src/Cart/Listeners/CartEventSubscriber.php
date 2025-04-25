<?php

namespace AndreiLungeanu\SimpleCart\Cart\Listeners;

use AndreiLungeanu\SimpleCart\Cart\Events\CartCleared;
use AndreiLungeanu\SimpleCart\Cart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Cart\Events\CartUpdated;
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
