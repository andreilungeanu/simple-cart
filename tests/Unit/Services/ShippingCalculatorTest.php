<?php

namespace AndreiLungeanu\SimpleCart\Tests\Unit\Services;

use AndreiLungeanu\SimpleCart\Services\ShippingCalculator;
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

test('calculates basic shipping cost', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(id: '1', name: 'Item 1', price: 10.00, quantity: 2),
            new CartItemDTO(id: '2', name: 'Item 2', price: 20.00, quantity: 1),
        ],
        shippingMethod: 'standard'
    );

    $calculator = new ShippingCalculator(new DefaultShippingProvider());
    $shippingCost = $calculator->calculate($cart);

    // Standard shipping fixed cost
    expect($shippingCost)->toBe(5.99);
});

test('applies free shipping threshold', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(id: '1', name: 'Item 1', price: 150.00, quantity: 1),
        ],
        shippingMethod: 'standard'
    );

    $calculator = new ShippingCalculator(new DefaultShippingProvider());
    expect($calculator->calculate($cart))->toBe(0.0);
});

test('shipping info includes VAT details', function () {
    $cart = new CartDTO(
        items: [new CartItemDTO(id: '1', name: 'Test', price: 10.00, quantity: 1)],
        shippingMethod: 'standard',
        taxZone: 'RO'
    );

    $calculator = new ShippingCalculator(new DefaultShippingProvider());
    $info = $calculator->getShippingInfo($cart);

    expect($info)
        ->toHaveKey('amount')
        ->toHaveKey('vat_rate')
        ->toHaveKey('vat_included');
});

test('respects VAT exemption for shipping', function () {
    $cart = new CartDTO(
        items: [new CartItemDTO(id: '1', name: 'Test', price: 10.00, quantity: 1)],
        shippingMethod: 'standard',
        taxZone: 'RO',
        vatExempt: true
    );

    $calculator = new ShippingCalculator(new DefaultShippingProvider());
    $info = $calculator->getShippingInfo($cart);

    expect($info['vat_rate'])->toBe(0.0);
});

test('handles shipping with included VAT', function () {
    $cart = new CartDTO(
        items: [new CartItemDTO(id: '1', name: 'Test', price: 10.00, quantity: 1)],
        shippingMethod: 'express',
        taxZone: 'RO'
    );

    $provider = new DefaultShippingProvider();
    $info = $provider->getRate($cart, 'express');

    expect($info['amount'])->toBe(15.99)
        ->and($info['vat_included'])->toBeFalse()
        ->and($info['vat_rate'])->toBeNull();
});
