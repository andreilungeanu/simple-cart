<?php

namespace AndreiLungeanu\SimpleCart\Tests\Unit\Services;

use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use Mockery;

afterEach(function () {
    Mockery::close();
});

test('shipping rate includes VAT information', function () {
    $provider = new DefaultShippingProvider;
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 50.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, taxZone: 'RO');

    $rate = $provider->getRate($cart, 'standard');

    expect($rate)->toBeInstanceOf(\AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingRateDTO::class);
    expect($rate->amount)->toBeFloat();
    expect(is_null($rate->vatRate) || is_float($rate->vatRate))->toBeTrue();
    expect($rate->vatIncluded)->toBeFalse();
});

test('returns correct structure for available shipping methods', function () {
    $provider = new DefaultShippingProvider;
    $cart = createCartInstanceForTesting();

    config([
        'simple-cart.shipping.settings.methods' => [
            'standard' => ['name' => 'Standard', 'cost' => 5.99, 'vat_rate' => 0.19],
            'express' => ['name' => 'Express', 'cost' => 15.99, 'vat_rate' => 0.19],
        ],
    ]);

    $methods = $provider->getAvailableMethods($cart);

    expect($methods)->toBeArray();
    // map to ids for assertion
    $ids = array_map(fn ($m) => $m->id, $methods);
    expect($ids)->toContain('standard')->and($ids)->toContain('express');

    $standard = array_values(array_filter($methods, fn ($m) => $m->id === 'standard'))[0];
    expect($standard->name)->toBe('Standard');
    expect(is_null($standard->description) || is_string($standard->description))->toBeTrue();
});
