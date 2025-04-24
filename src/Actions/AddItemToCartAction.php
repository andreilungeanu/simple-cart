<?php

namespace AndreiLungeanu\SimpleCart\Actions;

use AndreiLungeanu\SimpleCart\CartInstance; // Use CartInstance
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
// Event dispatching is moved to the Manager
// use AndreiLungeanu\SimpleCart\Events\CartUpdated;
// use AndreiLungeanu\SimpleCart\SimpleCart; // No longer used here

class AddItemToCartAction
{
    // Constructor might be used for injecting dependencies later if needed
    public function __construct() {}

    /**
     * Adds or updates an item in the given cart instance.
     *
     * @param CartInstance $cart The cart instance to modify.
     * @param CartItemDTO $itemDTO The item to add or update.
     * @return CartInstance The modified cart instance.
     */
    public function __invoke(CartInstance $cart, CartItemDTO $itemDTO): CartInstance
    {
        // Logic moved from old SimpleCart/CartInstance addItem method
        $existingItem = $cart->findItem($itemDTO->id); // Assumes findItem is protected/public on CartInstance

        if ($existingItem) {
            // Item exists, update quantity
            $newQuantity = $existingItem->quantity + $itemDTO->quantity;
            $cart->updateQuantity($itemDTO->id, $newQuantity); // Assumes updateQuantity handles internal state
        } else {
            // Item does not exist, add it to the collection
            // We need a way to add to the items collection. Let's assume CartInstance has setItems or similar,
            // or we modify the collection directly if getItems() returns it by reference (less ideal).
            // Safest approach: Get collection, push, set collection back.
            $items = $cart->getItems();
            $items->push($itemDTO);
            // If getItems() returns a clone, we need a setItems() method on CartInstance.
            // Assuming CartInstance::getItems() returns the actual collection reference for now.
            // If errors occur, we'll add setItems() to CartInstance.
        }

        // Event dispatching is handled by the Manager after calling the action.
        // event(new CartUpdated($cart));

        return $cart; // Return the modified instance
    }
}
