<?php

// Use the Facade for testing the public API
use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

// Removed duplicate helper function createTestItem - now defined in tests/Pest.php

// Set default config for tests in this file
beforeEach(function () {
    // Configure default shipping provider settings for tests
    config([
        'simple-cart.shipping.settings.free_shipping_threshold' => 100.00,
        'simple-cart.shipping.settings.methods' => [
            'standard' => ['name' => 'Standard', 'cost' => 5.99, 'vat_rate' => 0.19], // Assuming 19% VAT on standard shipping
            'express' => ['name' => 'Express', 'cost' => 15.99, 'vat_rate' => 0.19], // Assuming 19% VAT on express shipping
        ],
        'simple-cart.tax.settings.zones.RO.default_rate' => 0.19, // Ensure default tax rate
    ]);
});

test('applies shipping charge when below threshold', function () {
    $cartWrapper = Cart::create(taxZone: 'RO'); // Returns wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 90.00)) // Below 100.00 threshold
        ->setShippingMethod('standard', ['vat_included' => false]); // Explicitly state VAT not included

    // Shipping cost should be 5.99 as per config
    expect(Cart::shippingAmount($cartWrapper->getId()))->toBe(5.99); // Use manager method
});

test('applies free shipping at threshold', function () {
    $cartWrapper = Cart::create(taxZone: 'RO'); // Returns wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)) // At 100.00 threshold
        ->setShippingMethod('standard', ['vat_included' => false]);

    // Shipping cost should be 0.00
    expect(Cart::shippingAmount($cartWrapper->getId()))->toBe(0.00); // Use manager method
});

test('applies free shipping above threshold', function () {
    $cartWrapper = Cart::create(taxZone: 'RO'); // Returns wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 150.00)) // Above 100.00 threshold
        ->setShippingMethod('standard', ['vat_included' => false]);

    // Shipping cost should be 0.00
    expect(Cart::shippingAmount($cartWrapper->getId()))->toBe(0.00); // Use manager method
});


test('respects custom shipping threshold', function () {
    // Override threshold for this specific test
    config(['simple-cart.shipping.settings.free_shipping_threshold' => 200.00]);

    $cartWrapper = Cart::create(taxZone: 'RO'); // Returns wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 150.00)) // Below 200.00
        ->setShippingMethod('standard', ['vat_included' => false]);

    $cartId = $cartWrapper->getId();
    expect(Cart::shippingAmount($cartId))->toBe(5.99); // Standard cost applies // Use manager method

    // Add another item to reach threshold using the wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 50.00)); // Total 200.00

    // Shipping should now be free
    expect(Cart::shippingAmount($cartId))->toBe(0.00) // Use manager method
        ->and(Cart::subtotal($cartId))->toBe(200.00); // Use manager method
});

test('calculates total with shipping cost and tax', function () {
    $cartWrapper = Cart::create(taxZone: 'RO'); // Returns wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 90.00)) // Subtotal 90.00, Item Tax 17.10
        ->setShippingMethod('standard', ['vat_included' => false]); // Shipping 5.99

    $cartId = $cartWrapper->getId();
    // Shipping Tax = 5.99 * 0.19 = 1.1381 -> 1.14
    // Total Tax = 17.10 + 1.14 = 18.24
    // Total = Subtotal + Shipping + Total Tax
    // Total = 90.00 + 5.99 + 18.24 = 114.23

    expect(Cart::subtotal($cartId))->toBe(90.00) // Use manager method
        ->and(Cart::shippingAmount($cartId))->toBe(5.99) // Use manager method
        ->and(Cart::taxAmount($cartId))->toBe(18.24) // Item tax + Shipping tax // Use manager method
        ->and(Cart::total($cartId))->toBe(114.23); // Use manager method
});


test('handles shipping with included VAT correctly in total', function () {
    // Note: DefaultShippingProvider currently ignores vat_included flag from setShippingMethod info.
    // It calculates based on config cost and applies VAT if not exempt.
    // To test 'vat_included' properly, a custom provider or modification to DefaultShippingProvider is needed.
    // This test assumes the current DefaultShippingProvider behavior.

    $cartWrapper = Cart::create(taxZone: 'RO'); // Returns wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 90.00)) // Subtotal 90.00, Item Tax 17.10
        ->setShippingMethod('express', ['vat_included' => true, 'vat_rate' => 0.19]); // Express cost 15.99 (VAT will be calculated by provider)

    $cartId = $cartWrapper->getId();
    // Shipping Tax = 15.99 * 0.19 = 3.0381 -> 3.04
    // Total Tax = 17.10 + 3.04 = 20.14 (This is what CartCalculator should calculate now)
    // Total = Subtotal + Shipping + Total Tax
    // Total = 90.00 + 15.99 + 20.14 = 126.13

    $shippingAmount = Cart::shippingAmount($cartId); // Use manager method
    $taxAmount = Cart::taxAmount($cartId); // Use manager method
    $total = Cart::total($cartId); // Use manager method

    // Assert based on corrected logic: If vat_included is true, shipping tax is NOT added separately in getTaxAmount.
    // Total = Subtotal + Shipping (incl. VAT) + ItemTax = 90.00 + 15.99 + 17.10 = 123.09
    expect($shippingAmount)->toBe(15.99)
        ->and($taxAmount)->toBe(17.10) // Expect only item tax here
        ->and($total)->toBe(123.09); // Expect total based on shipping price including VAT + item tax
});

test('handles VAT exemption with shipping', function () {
    $cartWrapper = Cart::create(taxZone: 'RO'); // Returns wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 90.00)) // Subtotal 90.00
        ->setShippingMethod('standard', ['vat_included' => false]) // Shipping 5.99
        ->setVatExempt(true); // Set exempt

    $cartId = $cartWrapper->getId();
    // Total = Subtotal + Shipping
    // Total = 90.00 + 5.99 = 95.99

    expect(Cart::shippingAmount($cartId))->toBe(5.99) // Use manager method
        ->and(Cart::taxAmount($cartId))->toBe(0.00) // No tax // Use manager method
        ->and(Cart::total($cartId))->toBe(95.99); // Use manager method
});

test('returns zero shipping if no method set', function () {
    $cartWrapper = Cart::create(taxZone: 'RO'); // Returns wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 50.00)); // Below threshold

    // No setShippingMethod called
    expect(Cart::shippingAmount($cartWrapper->getId()))->toBe(0.00); // Use manager method
});
