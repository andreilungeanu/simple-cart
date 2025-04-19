<?php

namespace AndreiLungeanu\SimpleCart\Events;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CartDTO $cart
    ) {}
}
