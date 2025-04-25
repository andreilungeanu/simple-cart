<?php

namespace Tests\Unit\Cart;

use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
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
