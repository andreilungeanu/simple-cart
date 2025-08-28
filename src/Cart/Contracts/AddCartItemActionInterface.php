<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\CartInstance;

interface AddCartItemActionInterface
{
    /**
     * Adds or updates an item in the given cart instance.
     */
    public function handle(CartInstance $cart, CartItemDTO $itemDTO): CartInstance;
}
