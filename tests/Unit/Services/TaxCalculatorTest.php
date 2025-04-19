<?php

namespace AndreiLungeanu\SimpleCart\Tests\Unit\Services;

use AndreiLungeanu\SimpleCart\Services\TaxCalculator;
use AndreiLungeanu\SimpleCart\Services\DefaultTaxProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

test('calculates tax based on zone', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Test Product',
                price: 100.00,
                quantity: 1
            )
        ],
        taxZone: 'US'
    );

    $calculator = new TaxCalculator(new DefaultTaxProvider());

    expect($calculator->calculate($cart))->toBe(7.25);
});

test('returns zero tax for unknown zone', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Test Product',
                price: 100.00,
                quantity: 1
            )
        ],
        taxZone: 'UNKNOWN'
    );

    $calculator = new TaxCalculator(new DefaultTaxProvider());

    expect($calculator->calculate($cart))->toBe(0.0);
});

test('applies category specific tax rates', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Digital Book',
                price: 100.00,
                quantity: 1,
                category: 'books'
            )
        ],
        taxZone: 'RO'
    );

    $calculator = new TaxCalculator(new DefaultTaxProvider());
    expect($calculator->calculate($cart))->toBe(5.00);
});

test('handles mixed category tax rates', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Digital Book',
                price: 100.00,
                quantity: 1,
                category: 'books'
            ),
            new CartItemDTO(
                id: '2',
                name: 'Generic Item',
                price: 100.00,
                quantity: 1
            )
        ],
        taxZone: 'RO'
    );

    $calculator = new TaxCalculator(new DefaultTaxProvider());
    expect($calculator->calculate($cart))->toBe(24.00); // 5% on books + 19% on generic
});

test('applies default rate when category not specified', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Generic Item',
                price: 100.00,
                quantity: 1
            )
        ],
        taxZone: 'RO'
    );

    $calculator = new TaxCalculator(new DefaultTaxProvider());
    expect($calculator->calculate($cart))->toBe(19.00); // 19% default rate
});

test('returns zero tax when cart is VAT exempt', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Test Product',
                price: 100.00,
                quantity: 1
            )
        ],
        taxZone: 'RO',
        vatExempt: true
    );

    $calculator = new TaxCalculator(new DefaultTaxProvider());
    expect($calculator->calculate($cart))->toBe(0.0);
});

test('can switch VAT exemption status', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Test Product',
                price: 100.00,
                quantity: 1
            )
        ],
        taxZone: 'RO'
    );

    $calculator = new TaxCalculator(new DefaultTaxProvider());

    $initialTax = $calculator->calculate($cart);
    $cart->setVatExempt(true);
    $exemptTax = $calculator->calculate($cart);

    expect($initialTax)->toBe(19.00)
        ->and($exemptTax)->toBe(0.0);
});
