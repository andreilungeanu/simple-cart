<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Events;

use AndreiLungeanu\SimpleCart\Models\Cart;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Cart $cart,
        public string $action = 'updated', // 'created', 'updated', 'cleared', 'deleted'
        public array $metadata = [],
    ) {}
}
