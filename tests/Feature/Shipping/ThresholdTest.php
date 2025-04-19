<?php

namespace Tests\Feature\Shipping;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

beforeEach(function () {
    config()->set('simple-cart.shipping.settings.free_shipping_threshold', 100.00);
});

test('applies shipping charge when below threshold', function () {
    $cart = new CartDTO(taxZone: 'RO');
    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Item',
        price: 90.00,
        quantity: 1
    ));

    $cart->setShippingMethod('standard', [
        'amount' => 5.99,
        'vat_included' => false,
        'vat_rate' => null,
    ]);

    expect($cart->getShippingAmount())->toBe(5.99);
});

test('applies free shipping at threshold', function () {
    $cart = new CartDTO(taxZone: 'RO');
    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Item',
        price: 100.00,
        quantity: 1
    ));

    $cart->setShippingMethod('standard', [
        'amount' => 5.99,
        'vat_included' => false,
        'vat_rate' => null,
    ]);

    expect($cart->getShippingAmount())->toBe(0.00);
});

test('respects custom shipping threshold', function () {
    config(['simple-cart.shipping.settings.free_shipping_threshold' => 200.00]);

    $cart = new CartDTO(taxZone: 'RO');
    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Item',
        price: 150.00,
        quantity: 1
    ));

    $cart->setShippingMethod('standard', [
        'amount' => 5.99,
        'vat_included' => false,
        'vat_rate' => null,
    ]);

    expect($cart->getShippingAmount())->toBe(5.99);

    $cart->addItem(new CartItemDTO(
        id: '2',
        name: 'Item 2',
        price: 50.00,
        quantity: 1
    ));

    expect($cart->getShippingAmount())->toBe(0.00)
        ->and($cart->getSubtotal())->toBe(200.00);
});

test('handles empty cart with shipping costs', function () {
    $cart = new CartDTO(taxZone: 'RO');
    $cart->setShippingMethod('standard', [
        'amount' => 5.99,
        'vat_included' => false,
        'vat_rate' => null,
    ]);

    expect($cart->getShippingAmount())->toBe(5.99)
        ->and($cart->getTaxAmount())->toBe(1.14);
});

test('handles shipping with included VAT', function () {
    $cart = new CartDTO(taxZone: 'RO');
    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Item',
        price: 90.00,
        quantity: 1
    ));

    $cart->setShippingMethod('express', [
        'amount' => 15.99,
        'vat_included' => true,
        'vat_rate' => 0.19,
    ]);

    expect($cart->getShippingAmount())->toBe(15.99)
        ->and($cart->getTaxAmount())->toBe(17.10); // Base item tax
});

test('handles VAT exemption with shipping', function () {
    $cart = new CartDTO(taxZone: 'RO', vatExempt: true);
    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Item',
        price: 90.00,
        quantity: 1
    ));

    $cart->setShippingMethod('standard', [
        'amount' => 5.99,
        'vat_included' => false,
        'vat_rate' => null,
    ]);

    expect($cart->getShippingAmount())->toBe(5.99)
        ->and($cart->getTaxAmount())->toBe(0.0);
});
