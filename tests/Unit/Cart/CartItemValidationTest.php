<?php

namespace Tests\Unit\Cart;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use InvalidArgumentException;

test('validates item quantity is positive', function () {
    expect(fn () => new CartItemDTO(
        id: '1',
        name: 'Test',
        price: 10.00,
        quantity: -1,
        metadata: []
    ))->toThrow(InvalidArgumentException::class);
});

test('validates price is not greater than maximum allowed', function () {
    expect(fn () => new CartItemDTO(
        id: '1',
        name: 'Test',
        price: PHP_FLOAT_MAX,
        quantity: 1,
        metadata: []
    ))->toThrow(InvalidArgumentException::class);
});

test('validates price precision and rounding', function () {
    $cart = new CartDTO(taxZone: 'RO');
    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Test',
        price: 10.999,
        quantity: 1,
        metadata: []
    ));

    expect($cart->getSubtotal())->toBe(11.00);
});
