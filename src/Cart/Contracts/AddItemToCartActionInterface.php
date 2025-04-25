<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;

interface AddItemToCartActionInterface
{
    /**
     * Adds or updates an item in the given cart instance.
     *
     * @param CartInstance $cart The cart instance to modify.
     * @param CartItemDTO $itemDTO The item to add or update.
     * @return CartInstance The modified cart instance.
     */
    public function __invoke(CartInstance $cart, CartItemDTO $itemDTO): CartInstance;
}
