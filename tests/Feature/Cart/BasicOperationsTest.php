<?php

namespace Tests\Feature\Cart;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use AndreiLungeanu\SimpleCart\SimpleCart;

test('can create a new cart', function () {
    $cart = app(SimpleCart::class)->create();
    expect($cart->get())->toBeInstanceOf(CartDTO::class);
});

test('can add items to cart', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 99.99,
            quantity: 1
        ));

    expect($cart->get()->getItems())->toHaveCount(1);
});

test('can update item quantity', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 99.99,
            quantity: 1
        ))
        ->updateQuantity('1', 2);

    expect($cart->get()->getItems()->first()->quantity)->toBe(2);
});

test('throws exception when accessing empty cart', function () {
    $cart = app(SimpleCart::class);
    expect(fn () => $cart->updateQuantity('1', 2))
        ->toThrow(CartException::class, 'Cart not found');
});
