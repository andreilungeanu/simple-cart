<?php

namespace Tests\Feature\Cart;

use AndreiLungeanu\SimpleCart\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

test('can save and retrieve cart', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 100.00,
            quantity: 1
        ))
        ->save();

    $cartId = $cart->get()->id;
    $loadedCart = app(SimpleCart::class)->find($cartId);
    expect($loadedCart->get()->getItems())->toHaveCount(1);
});
