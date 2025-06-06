<?php

namespace AndreiLungeanu\SimpleCart\Cart\Events;

use AndreiLungeanu\SimpleCart\CartInstance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param CartInstance $cart The cart instance that was updated.
     */
    public function __construct(
        public readonly CartInstance $cart
    ) {}
}
