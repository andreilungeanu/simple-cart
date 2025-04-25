<?php

namespace AndreiLungeanu\SimpleCart\Cart\Actions;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\Contracts\AddItemToCartActionInterface;

class AddItemToCartAction implements AddItemToCartActionInterface
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
            $existingItem = $items->get($existingItemIndex);
            $newQuantity = $existingItem->quantity + $itemDTO->quantity;
            $items->put($existingItemIndex, $existingItem->withQuantity($newQuantity));
        } else {
            $items->push($itemDTO);
        }

        return $cart;
    }
}
