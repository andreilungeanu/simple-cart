<?php

namespace AndreiLungeanu\SimpleCart\Actions;

use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\SimpleCart;

class AddItemToCartAction
{
    public function __construct() {}

    /**
     * Adds an item to the cart instance.
     *
     * @param SimpleCart $cart The cart instance to modify.
     * @param CartItemDTO $item The item DTO to add.
     * @return SimpleCart The modified cart instance.
     */
    public function execute(SimpleCart $cart, CartItemDTO $item): SimpleCart
    {
        $cart->getItems()->push($item);
        event(new CartUpdated($cart));

        return $cart;
    }
}
