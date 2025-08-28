<?php

namespace AndreiLungeanu\SimpleCart\Cart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $cartId
    ) {}
}
