<?php

namespace Tests\Unit\Cart;

// Remove CartDTO import
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use InvalidArgumentException;

test('validates item quantity is positive', function () {
    expect(fn() => new CartItemDTO(
        id: '1',
        name: 'Test',
        price: 10.00,
        quantity: -1,
        metadata: []
    ))->toThrow(InvalidArgumentException::class);
});

test('validates price is not greater than maximum allowed', function () {
    expect(fn() => new CartItemDTO(
        id: '1',
        name: 'Test',
        price: PHP_FLOAT_MAX,
        quantity: 1,
        metadata: []
    ))->toThrow(InvalidArgumentException::class);
});

// Remove test for price precision/rounding - it tested CartDTO::getSubtotal which is removed.
// Subtotal calculation is tested in Feature/Cart/CalculationsTest.php
