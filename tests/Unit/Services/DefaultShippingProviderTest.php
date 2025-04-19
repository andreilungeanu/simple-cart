<?php

namespace AndreiLungeanu\SimpleCart\Tests\Unit\Services;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;

test('shipping rate includes VAT information', function () {
    $provider = new DefaultShippingProvider;
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Test Product',
                price: 50.00,
                quantity: 1
            ),
        ]
    );

    $rate = $provider->getRate($cart, 'standard');

    expect($rate)
        ->toHaveKey('amount')
        ->toHaveKey('vat_rate')
        ->toHaveKey('vat_included')
        ->and($rate['vat_included'])->toBeFalse();
});

test('returns null VAT rate for default shipping methods', function () {
    $provider = new DefaultShippingProvider;
    $cart = new CartDTO;

    $methods = $provider->getAvailableMethods($cart);

    expect($methods['standard']['vat_rate'])->toBeNull()
        ->and($methods['express']['vat_rate'])->toBeNull();
});
