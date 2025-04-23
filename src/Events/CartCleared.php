<?php

namespace AndreiLungeanu\SimpleCart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartCleared
{
    use Dispatchable, SerializesModels;

    // Add cartId property
    public function __construct(
        public readonly string $cartId
    ) {}
}
