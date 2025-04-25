<?php

namespace AndreiLungeanu\SimpleCart\Cart\Actions;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\Contracts\AddItemToCartActionInterface; // Add interface

class AddItemToCartAction implements AddItemToCartActionInterface // Implement interface
{
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
        $items = $cart->getItems();
        $existingItemIndex = $items->search(fn(CartItemDTO $item) => $item->id === $itemDTO->id);

        if ($existingItemIndex !== false) {
            // Item exists, update quantity
            $existingItem = $items->get($existingItemIndex);
            $newQuantity = $existingItem->quantity + $itemDTO->quantity;
            // Replace item with a new DTO instance having the updated quantity
            $items->put($existingItemIndex, $existingItem->withQuantity($newQuantity));
        } else {
            // Item does not exist, add it
            $items->push($itemDTO);
        }
        // No need to call setItems on $cart here, as the action modifies the collection directly.
        // The CartManager will handle saving the modified CartInstance.

        return $cart;
    }
}
