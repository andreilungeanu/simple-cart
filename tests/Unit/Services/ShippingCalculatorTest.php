<?php

namespace AndreiLungeanu\SimpleCart\Tests\Unit\Services;

use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\Services\Calculation\ShippingCalculator;
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
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

    // now returns a ShippingRateDTO
    expect($info)->toBeInstanceOf(\AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingRateDTO::class);
    expect($info->amount)->toBeFloat();
    expect(is_null($info->vatRate) || is_float($info->vatRate))->toBeTrue();
    expect($info->vatIncluded)->toBeBool();
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

    expect($info)->toBeInstanceOf(\AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingRateDTO::class);
    expect($info->vatRate)->toBe(0.0);
});

test('calculator uses provider to get shipping info', function () {
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 10.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, shippingMethod: 'express', taxZone: 'RO');

    $mockProvider = Mockery::mock(DefaultShippingProvider::class);
    $mockProvider->shouldReceive('getRate')
        ->once()
        ->with($cart, 'express')
        ->andReturn(new \AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingRateDTO(amount: 15.99, vatRate: 0.19, vatIncluded: false));

    $calculator = new ShippingCalculator($mockProvider);

    $info = $calculator->getShippingInfo($cart);

    expect($info->amount)->toBe(15.99);
});
