<?php

namespace AndreiLungeanu\SimpleCart\Actions;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

class AddItemToCartAction
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
        $existingItem = $cart->findItem($itemDTO->id);

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $itemDTO->quantity;
            $cart->updateQuantity($itemDTO->id, $newQuantity);
        } else {
            $items = $cart->getItems();
            $items->push($itemDTO);
        }

        return $cart;
    }
}
