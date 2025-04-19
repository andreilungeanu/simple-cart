<?php

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

test('empty cart returns zero total', function () {
    $cart = new CartDTO();
    expect($cart->calculateTotal())->toBe(0.0)
        ->and($cart->getItems()->isEmpty())->toBeTrue();
});

test('can get item count with multiple quantities', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(id: '1', name: 'Product 1', price: 10.00, quantity: 2),
            new CartItemDTO(id: '2', name: 'Product 2', price: 20.00, quantity: 3),
        ]
    );

    expect($cart->getItemCount())->toBe(5)
        ->and($cart->getItems())->toHaveCount(2);
});

test('throws exception when adding invalid quantity', function () {
    $cart = new CartDTO();
    expect(fn() => $cart->updateItemQuantity('1', -1))
        ->toThrow(InvalidArgumentException::class, 'Quantity must be positive');
});
