<?php

namespace AndreiLungeanu\SimpleCart\Tests\Unit\Services;

use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\Services\ShippingCalculator;
use AndreiLungeanu\SimpleCart\SimpleCart;
use AndreiLungeanu\SimpleCart\Repositories\CartRepository;
use AndreiLungeanu\SimpleCart\Services\CartCalculator;
use AndreiLungeanu\SimpleCart\Actions\AddItemToCartAction;
use Mockery;

afterEach(function () {
    Mockery::close();
});

beforeEach(function () {
    config([
        'simple-cart.shipping.settings.free_shipping_threshold' => 100.00,
        'simple-cart.shipping.settings.methods' => [
            'standard' => ['name' => 'Standard', 'cost' => 5.99, 'vat_rate' => 0.19],
            'express' => ['name' => 'Express', 'cost' => 15.99, 'vat_rate' => 0.19],
        ],
        'simple-cart.tax.settings.zones.RO.default_rate' => 0.19,
    ]);
});


test('calculates basic shipping cost', function () {
    $cartItems = [
        new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 2, price: 10.00),
        new CartItemDTO(id: '2', name: 'Test Product 2', quantity: 1, price: 20.00),
    ];
    $cart = createCartInstanceForTesting(items: $cartItems, shippingMethod: 'standard');

    $calculator = new ShippingCalculator(new DefaultShippingProvider);

    $shippingCost = $calculator->calculate($cart);

    expect($shippingCost)->toBe(5.99);
});

test('applies free shipping threshold', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 150.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, shippingMethod: 'standard');
    $calculator = new ShippingCalculator(new DefaultShippingProvider);

    expect($calculator->calculate($cart))->toBe(0.0);
});

test('shipping info includes VAT details', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 10.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, shippingMethod: 'standard', taxZone: 'RO');
    $calculator = new ShippingCalculator(new DefaultShippingProvider);

    $info = $calculator->getShippingInfo($cart);

    expect($info)
        ->toHaveKey('amount')
        ->toHaveKey('vat_rate')
        ->toHaveKey('vat_included');
});

test('respects VAT exemption for shipping', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 10.00)];
    $cart = createCartInstanceForTesting(
        items: $cartItems,
        shippingMethod: 'standard',
        taxZone: 'RO',
        vatExempt: true
    );
    $calculator = new ShippingCalculator(new DefaultShippingProvider);

    $info = $calculator->getShippingInfo($cart);

    expect($info['vat_rate'])->toBe(0.0);
});

test('calculator uses provider to get shipping info', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 10.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, shippingMethod: 'express', taxZone: 'RO');

    $mockProvider = Mockery::mock(DefaultShippingProvider::class);
    $mockProvider->shouldReceive('getRate')
        ->once()
        ->with($cart, 'express')
        ->andReturn(['amount' => 15.99, 'vat_rate' => 0.19, 'vat_included' => false]);

    $calculator = new ShippingCalculator($mockProvider);

    $info = $calculator->getShippingInfo($cart);

    expect($info['amount'])->toBe(15.99);
});
