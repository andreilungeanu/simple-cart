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

test('handles VAT exemption scenarios', function () {
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
        price: 50.00,
        quantity: 1,
        category: 'books'
    ));

    $normalTax = $cart->getTaxAmount();
    $cart->setVatExempt(true);
    $exemptTax = $cart->getTaxAmount();

    expect($normalTax)->toBe(21.50) // 19% of 100 + 5% of 50
        ->and($exemptTax)->toBe(0.0);
});

test('applies different VAT rates by category', function () {
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

    $cart->addItem(new CartItemDTO(
        id: '3',
        name: 'Food',
        price: 100.00,
        quantity: 1,
        category: 'food'
    ));

    expect($cart->getTaxAmount())->toBe(33.00); // 19% + 5% + 9%
});
