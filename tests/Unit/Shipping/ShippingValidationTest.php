<?php

namespace Tests\Unit\Shipping;

// Remove CartDTO import
use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart; // Use Facade
use InvalidArgumentException;

test('validates vat rate is between 0 and 1 in setShippingMethod', function () {
    Cart::create(); // Need a cart instance to call the method on

    // Test upper bound
    expect(fn() => Cart::setShippingMethod('test-upper', [
        'vat_rate' => 1.5, // Invalid rate > 1
    ]))->toThrow(InvalidArgumentException::class, 'VAT rate must be between 0 and 1');

    // Test lower bound
    expect(fn() => Cart::setShippingMethod('test-lower', [
        'vat_rate' => -0.1, // Invalid rate < 0
    ]))->toThrow(InvalidArgumentException::class, 'VAT rate must be between 0 and 1');

    // Test valid rate (should not throw)
    expect(fn() => Cart::setShippingMethod('test-valid', [
        'vat_rate' => 0.19, // Valid rate
    ]))->not->toThrow(InvalidArgumentException::class);

    // Test null rate (should not throw)
    expect(fn() => Cart::setShippingMethod('test-null', [
        'vat_rate' => null,
    ]))->not->toThrow(InvalidArgumentException::class);

    // Test missing rate (should not throw)
    expect(fn() => Cart::setShippingMethod('test-missing', []))
        ->not->toThrow(InvalidArgumentException::class);
});
