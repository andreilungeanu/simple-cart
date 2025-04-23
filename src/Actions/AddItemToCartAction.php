<?php

namespace AndreiLungeanu\SimpleCart\Actions;

use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\SimpleCart;
// No longer need Dispatcher contract

class AddItemToCartAction
{
    // No need to inject Dispatcher if using event() helper
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
        // Basic validation or checks could go here if needed

        // Modify the cart state directly (assuming properties are accessible or via setters)
        // Since items is protected, we need a way to add. Let's assume a public method on SimpleCart or modify visibility.
        // For now, let's add a temporary public method `internalAddItem` to SimpleCart for the action to use.
        // Alternatively, Actions could be friends or operate differently.
        // Let's stick to modifying SimpleCart::addItem to *use* this action first.

        // The core logic:
        $cart->getItems()->push($item); // Access collection and push
        event(new CartUpdated($cart)); // Use event() helper

        return $cart; // Return the modified cart
    }
}
