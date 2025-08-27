<?php

namespace AndreiLungeanu\SimpleCart\Tests\Unit\Services;

use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\Services\Calculation\TaxCalculator;
use AndreiLungeanu\SimpleCart\Services\DefaultTaxProvider;
use Mockery;

afterEach(function () {
    Mockery::close();
});

beforeEach(function () {
    config([
        'simple-cart.tax.settings.zones' => [
            'RO' => [
                'default_rate' => 0.19,
                'rates_by_category' => [
                    'books' => 0.05,
                    'food' => 0.09,
                ],
                'apply_to_shipping' => true,
            ],
            'US' => [
                'default_rate' => 0.0725,
                'rates_by_category' => [],
                'apply_to_shipping' => false,
            ],
        ],
    ]);
});

test('calculates tax based on zone', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 100.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, taxZone: 'US');
    $calculator = new TaxCalculator(new DefaultTaxProvider);

    expect($calculator->calculate($cart))->toBe(7.25);
});

test('returns zero tax for unknown zone', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 100.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, taxZone: 'UNKNOWN');
    $calculator = new TaxCalculator(new DefaultTaxProvider);

    expect($calculator->calculate($cart))->toBe(0.0);
});

test('applies category specific tax rates', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 100.00, category: 'books')];
    $cart = createCartInstanceForTesting(items: $cartItems, taxZone: 'RO');
    $calculator = new TaxCalculator(new DefaultTaxProvider);

    expect($calculator->calculate($cart))->toBe(5.00);
});

test('handles mixed category tax rates', function () {
    $cartItems = [
        new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 100.00, category: 'books'),
        new CartItemDTO(id: '2', name: 'Test Product 2', quantity: 1, price: 100.00),
    ];
    $cart = createCartInstanceForTesting(items: $cartItems, taxZone: 'RO');
    $calculator = new TaxCalculator(new DefaultTaxProvider);

    expect($calculator->calculate($cart))->toBe(24.00);
});

test('applies default rate when category not specified', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 100.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, taxZone: 'RO');
    $calculator = new TaxCalculator(new DefaultTaxProvider);
    expect($calculator->calculate($cart))->toBe(19.00);
});

test('returns zero tax when cart is VAT exempt', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 100.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, taxZone: 'RO', vatExempt: true);
    $calculator = new TaxCalculator(new DefaultTaxProvider);
    expect($calculator->calculate($cart))->toBe(0.0);
});
