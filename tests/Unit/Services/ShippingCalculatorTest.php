<?php

namespace AndreiLungeanu\SimpleCart\Tests\Unit\Services;

// Remove CartDTO import
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Services\DefaultShippingProvider;
use AndreiLungeanu\SimpleCart\Services\ShippingCalculator;
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

// Set default config for tests in this file
beforeEach(function () {
    config([
        'simple-cart.shipping.settings.free_shipping_threshold' => 100.00,
        'simple-cart.shipping.settings.methods' => [
            'standard' => ['name' => 'Standard', 'cost' => 5.99, 'vat_rate' => 0.19],
            'express' => ['name' => 'Express', 'cost' => 15.99, 'vat_rate' => 0.19],
        ],
        'simple-cart.tax.settings.zones.RO.default_rate' => 0.19, // Needed for VAT exemption test
    ]);
});


test('calculates basic shipping cost', function () {
    // Arrange
    $cartItems = [
        new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 2, price: 10.00), // 20.00
        new CartItemDTO(id: '2', name: 'Test Product 2', quantity: 1, price: 20.00), // 20.00 -> Subtotal 40.00 (below threshold)
    ];
    // Create cart instance with items and shipping method
    $cart = createCartInstanceForTesting(items: $cartItems, shippingMethod: 'standard'); // Use correct helper name

    // Instantiate calculator with default provider
    $calculator = new ShippingCalculator(new DefaultShippingProvider);

    // Act
    $shippingCost = $calculator->calculate($cart); // Pass SimpleCart instance

    // Assert: Standard shipping fixed cost from config
    expect($shippingCost)->toBe(5.99);
});

test('applies free shipping threshold', function () {
    // Arrange
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 150.00)]; // Above threshold
    $cart = createCartInstanceForTesting(items: $cartItems, shippingMethod: 'standard'); // Use correct helper name
    $calculator = new ShippingCalculator(new DefaultShippingProvider);

    // Act & Assert
    expect($calculator->calculate($cart))->toBe(0.0);
});

test('shipping info includes VAT details', function () {
    // Arrange
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 10.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, shippingMethod: 'standard', taxZone: 'RO'); // Use correct helper name
    $calculator = new ShippingCalculator(new DefaultShippingProvider);

    // Act
    $info = $calculator->getShippingInfo($cart); // Pass SimpleCart instance

    // Assert
    expect($info)
        ->toHaveKey('amount')
        ->toHaveKey('vat_rate')
        ->toHaveKey('vat_included');
    // ->and($info['vat_rate'])->toBe(0.19); // Check specific rate based on config/logic if needed
});

test('respects VAT exemption for shipping', function () {
    // Arrange
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 10.00)];
    $cart = createCartInstanceForTesting( // Use correct helper name
        items: $cartItems,
        shippingMethod: 'standard',
        taxZone: 'RO',
        vatExempt: true // Set exempt
    );
    $calculator = new ShippingCalculator(new DefaultShippingProvider);

    // Act
    $info = $calculator->getShippingInfo($cart); // Pass SimpleCart instance

    // Assert: VAT rate should be 0 when exempt
    expect($info['vat_rate'])->toBe(0.0);
});

// This test seems to be testing the provider, not the calculator. Moved similar logic to provider test.
// Keeping a simplified version here to ensure calculator calls provider correctly.
test('calculator uses provider to get shipping info', function () {
    // Arrange
    $cartItems = [new CartItemDTO(id: '1', name: 'Test Product 1', quantity: 1, price: 10.00)];
    $cart = createCartInstanceForTesting(items: $cartItems, shippingMethod: 'express', taxZone: 'RO'); // Use correct helper name

    // Mock the provider
    $mockProvider = Mockery::mock(DefaultShippingProvider::class);
    $mockProvider->shouldReceive('getRate')
        ->once()
        ->with($cart, 'express') // Expect call with cart and method
        ->andReturn(['amount' => 15.99, 'vat_rate' => 0.19, 'vat_included' => false]); // Return expected structure

    $calculator = new ShippingCalculator($mockProvider);

    // Act
    $info = $calculator->getShippingInfo($cart);

    // Assert
    expect($info['amount'])->toBe(15.99);
});
