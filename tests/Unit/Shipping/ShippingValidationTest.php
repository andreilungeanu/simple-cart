<?php

namespace Tests\Unit\Shipping;

// Remove CartDTO import
use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart; // Use Facade
use InvalidArgumentException;
use Illuminate\Foundation\Testing\RefreshDatabase; // Add RefreshDatabase

uses(RefreshDatabase::class); // Apply trait

test('validates vat rate is between 0 and 1 in setShippingMethod', function () {
    $cart = Cart::create(); // Create a cart instance
    $cartId = $cart->getId(); // Get its ID

    // Test upper bound
    expect(fn() => Cart::setShippingMethod($cartId, 'test-upper', [ // Pass cartId
        'vat_rate' => 1.5, // Invalid rate > 1
    ]))->toThrow(InvalidArgumentException::class, 'VAT rate must be between 0 and 1');

    // Test lower bound
    expect(fn() => Cart::setShippingMethod($cartId, 'test-lower', [ // Pass cartId
        'vat_rate' => -0.1, // Invalid rate < 0
    ]))->toThrow(InvalidArgumentException::class, 'VAT rate must be between 0 and 1');

    // Test valid rate (should not throw)
    expect(fn() => Cart::setShippingMethod($cartId, 'test-valid', [ // Pass cartId
        'vat_rate' => 0.19, // Valid rate
    ]))->not->toThrow(InvalidArgumentException::class);

    // Test null rate (should not throw)
    expect(fn() => Cart::setShippingMethod($cartId, 'test-null', [ // Pass cartId
        'vat_rate' => null,
    ]))->not->toThrow(InvalidArgumentException::class);

    // Test missing rate (should not throw)
    expect(fn() => Cart::setShippingMethod($cartId, 'test-missing', [])) // Pass cartId
        ->not->toThrow(InvalidArgumentException::class);
});
