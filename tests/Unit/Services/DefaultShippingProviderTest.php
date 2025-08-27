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

    expect($rate)
        ->toHaveKey('amount')
        ->toHaveKey('vat_rate')
        ->toHaveKey('vat_included')
        ->and($rate['vat_included'])->toBeFalse();
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

    expect($methods)->toBeArray()
        ->toHaveKeys(['standard', 'express'])
        ->and($methods['standard'])->toHaveKeys(['name', 'vat_rate', 'vat_included'])
        ->and($methods['standard']['name'])->toBe('Standard')
        ->and($methods['standard']['vat_rate'])->toBeNull()
        ->and($methods['standard']['vat_included'])->toBeFalse();
});
