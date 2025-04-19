<?php

namespace Tests\Feature\Tax;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

test('calculates correct tax for mixed cart', function () {
    $cart = new CartDTO(taxZone: 'RO');

    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Regular',
        price: 100.00,
        quantity: 1
    ));

    $cart->addItem(new CartItemDTO(
        id: '2',
        name: 'Book',
        price: 100.00,
        quantity: 1,
        category: 'books'
    ));

    expect($cart->getTaxAmount())->toBe(24.00); // 19% + 5%
});

test('applies category specific tax rates', function () {
    $cart = new CartDTO(taxZone: 'RO');

    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Book',
        price: 100.00,
        quantity: 1,
        category: 'books'
    ));

    expect($cart->getTaxAmount())->toBe(5.00); // 5% for books
});

test('uses default tax rate when no category specified', function () {
    $cart = new CartDTO(taxZone: 'RO');

    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Regular Item',
        price: 100.00,
        quantity: 1
    ));

    expect($cart->getTaxAmount())->toBe(19.00); // 19% default rate
});
