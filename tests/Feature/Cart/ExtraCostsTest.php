<?php

namespace Tests\Feature\Cart;

use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\SimpleCart;

test('can add extra costs to cart', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 100.00,
            quantity: 1
        ));

    $cart->get()->addExtraCost(new ExtraCostDTO(
        name: 'Gift Wrapping',
        amount: 5.00
    ));

    expect($cart->total())->toBe(105.00);
});

test('can calculate percentage based extra costs', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 100.00,
            quantity: 1
        ));

    $cart->get()->addExtraCost(new ExtraCostDTO(
        name: 'Handling Fee',
        amount: 10,
        type: 'percentage'
    ));

    expect($cart->total())->toBe(110.00);
});
