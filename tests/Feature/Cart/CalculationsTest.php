<?php

namespace Tests\Feature\Cart;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;

test('maintains precision with percentage-based extra costs', function () {
    $cart = new CartDTO(taxZone: 'RO');

    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Item',
        price: 99.99,
        quantity: 1,
        metadata: []
    ));

    $cart->addExtraCost(new ExtraCostDTO(
        name: 'Handling',
        amount: 5,
        type: 'percentage'
    ));

    expect($cart->getExtraCostsTotal())->toBe(5.00)
        ->and($cart->calculateTotal())->toBe(124.94);
});

test('handles rounding correctly for tax calculations', function () {
    $cart = new CartDTO(taxZone: 'RO');

    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Item',
        price: 33.33,
        quantity: 3,
        metadata: []
    ));

    expect($cart->getTaxAmount())->toBe(19.00);
});

test('maintains precision in calculations', function () {
    $cart = new CartDTO(taxZone: 'RO');

    for ($i = 0; $i < 10; $i++) {
        $cart->addItem(new CartItemDTO(
            id: (string)$i,
            name: "Item $i",
            price: 9.99,
            quantity: 1,
            metadata: []
        ));
    }

    expect($cart->getSubtotal())->toBe(99.90)
        ->and($cart->getTaxAmount())->toBe(18.98);
});
