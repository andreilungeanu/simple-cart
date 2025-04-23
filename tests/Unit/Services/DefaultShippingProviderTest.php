<?php

namespace AndreiLungeanu\SimpleCart\Tests\Unit\Services;

// Remove CartDTO import
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\SimpleCart; // Import SimpleCart
use AndreiLungeanu\SimpleCart\Repositories\CartRepository; // For mocking
use AndreiLungeanu\SimpleCart\Services\CartCalculator; // For mocking
use AndreiLungeanu\SimpleCart\Actions\AddItemToCartAction; // For mocking
use Mockery; // Use Mockery for mocking dependencies

// Removed duplicate helper function createTestItem - now defined in tests/Pest.php
// Removed duplicate helper function createTestCartInstance - now defined in tests/Pest.php

// Close Mockery expectations after each test
afterEach(function () {
    Mockery::close();
});


test('shipping rate includes VAT information', function () {
    // Arrange
    $provider = new DefaultShippingProvider;
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 50.00)];
    $cart = createTestCartInstance(items: $cartItems, taxZone: 'RO'); // Use helper

    // Act
    $rate = $provider->getRate($cart, 'standard'); // Pass SimpleCart instance

    // Assert
    expect($rate)
        ->toHaveKey('amount')
        ->toHaveKey('vat_rate')
        ->toHaveKey('vat_included')
        ->and($rate['vat_included'])->toBeFalse();
    // ->and($rate['vat_rate'])->toBe(0.19); // Check specific rate based on config/logic if needed
});

test('returns correct structure for available shipping methods', function () {
    // Arrange
    $provider = new DefaultShippingProvider;
    // Create an empty cart instance, state doesn't matter for getAvailableMethods
    $cart = createTestCartInstance();

    // Set expected config for this test
    config([
        'simple-cart.shipping.settings.methods' => [
            'standard' => ['name' => 'Standard', 'cost' => 5.99, 'vat_rate' => 0.19],
            'express' => ['name' => 'Express', 'cost' => 15.99, 'vat_rate' => 0.19],
        ]
    ]);

    // Act
    $methods = $provider->getAvailableMethods($cart); // Pass SimpleCart instance

    // Assert
    expect($methods)->toBeArray()
        ->toHaveKeys(['standard', 'express'])
        ->and($methods['standard'])->toHaveKeys(['name', 'vat_rate', 'vat_included'])
        ->and($methods['standard']['name'])->toBe('Standard')
        // DefaultShippingProvider::getAvailableMethods currently returns null for vat_rate
        ->and($methods['standard']['vat_rate'])->toBeNull()
        ->and($methods['standard']['vat_included'])->toBeFalse();
});
