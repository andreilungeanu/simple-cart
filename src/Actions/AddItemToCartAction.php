<?php

namespace AndreiLungeanu\SimpleCart\Actions;

use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\SimpleCart;
use InvalidArgumentException;
// No longer need Dispatcher contract

class AddItemToCartAction
{
    // No need to inject Dispatcher if using event() helper
    public function __construct() {}

    /**
     * Adds an item (DTO or array) to the cart instance.
     * If an array is provided, it must contain the required keys for CartItemDTO.
     *
     * @param SimpleCart $cart The cart instance to modify.
     * @param CartItemDTO|array $item The item DTO or an associative array representing the item.
     * @return SimpleCart The modified cart instance.
     * @throws InvalidArgumentException If the provided array is invalid.
     */
    public function execute(SimpleCart $cart, CartItemDTO|array $item): SimpleCart
    {
        if (is_array($item)) {
            // Basic validation for required keys if it's an array
            if (!isset($item['id'], $item['name'], $item['price'], $item['quantity'])) {
                throw new InvalidArgumentException('Item array must contain id, name, price, and quantity.');
            }
            // Convert array to DTO
            $itemDTO = new CartItemDTO(...$item);
        } elseif ($item instanceof CartItemDTO) {
            $itemDTO = $item;
        } else {
            // This case should ideally not be reached due to type hinting, but good practice
            throw new InvalidArgumentException('Item must be a CartItemDTO instance or an associative array.');
        }

        // Modify the cart state directly
        // Since items is protected, we need a way to add. Let's assume a public method on SimpleCart or modify visibility.
        // For now, let's add a temporary public method `internalAddItem` to SimpleCart for the action to use.
        // Alternatively, Actions could be friends or operate differently.
        // The core logic:
        $cart->getItems()->push($itemDTO); // Push the validated/created DTO
        event(new CartUpdated($cart)); // Use event() helper

        return $cart; // Return the modified cart
    }
}
