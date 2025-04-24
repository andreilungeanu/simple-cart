<?php

namespace AndreiLungeanu\SimpleCart\Actions;

use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\SimpleCart;

class AddItemToCartAction
{
    public function __construct() {}

    /**
     * Make the action invokable.
     *
     * @param SimpleCart $cart
     * @param CartItemDTO $item
     * @return SimpleCart
     */
    public function __invoke(SimpleCart $cart, CartItemDTO $item): SimpleCart
    {
        $cart->getItems()->push($item);
        event(new CartUpdated($cart));

        return $cart;
    }
}
