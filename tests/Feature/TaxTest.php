<?php

// Use the Facade for testing the public API
use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;

// Removed duplicate helper function createTestItem - now defined in tests/Pest.php

// Set default config for tests in this file
beforeEach(function () {
    // Configure default tax provider settings for tests
    config([
        'simple-cart.tax.settings.zones' => [
            'RO' => [
                'default_rate' => 0.19, // 19%
                'rates_by_category' => [
                    'books' => 0.05, // 5%
                    'food' => 0.09,  // 9%
                ],
                'apply_to_shipping' => true, // Assume tax applies to shipping by default
            ],
            'EU' => [
                'default_rate' => 0.21,
                'rates_by_category' => [],
                'apply_to_shipping' => true,
            ],
        ],
    ]);
});

test('uses default tax rate when no category specified', function () {
    $cart = Cart::create(taxZone: 'RO');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)); // No category

    // Tax = 100.00 * 0.19 = 19.00
    expect(Cart::taxAmount($cartId))->toBe(19.00); // Use new method with cartId
});

test('applies category specific tax rates', function () {
    $cart = Cart::create(taxZone: 'RO');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00, category: 'books')); // Books category

    // Tax = 100.00 * 0.05 = 5.00
    expect(Cart::taxAmount($cartId))->toBe(5.00); // Use new method with cartId
});

test('calculates correct tax for mixed category cart', function () {
    $cart = Cart::create(taxZone: 'RO');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)); // Regular item, Tax = 19.00
    Cart::addItem($cartId, new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 100.00, category: 'books')); // Book item, Tax = 5.00

    // Total Tax = 19.00 + 5.00 = 24.00
    expect(Cart::taxAmount($cartId))->toBe(24.00); // Use new method with cartId
});

test('applies different VAT rates by category correctly', function () {
    $cart = Cart::create(taxZone: 'RO');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)); // Regular item, Tax = 19.00
    Cart::addItem($cartId, new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 100.00, category: 'books')); // Book item, Tax = 5.00
    Cart::addItem($cartId, new CartItemDTO(id: 'item-3', name: 'Test Product item-3', quantity: 1, price: 100.00, category: 'food')); // Food item, Tax = 9.00

    // Total Tax = 19.00 + 5.00 + 9.00 = 33.00
    expect(Cart::taxAmount($cartId))->toBe(33.00); // Use new method with cartId
});


test('handles VAT exemption correctly', function () {
    $cart = Cart::create(taxZone: 'RO');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)); // Regular item
    Cart::addItem($cartId, new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 50.00, category: 'books')); // Book item

    // Normal Tax = (100 * 0.19) + (50 * 0.05) = 19.00 + 2.50 = 21.50
    expect(Cart::taxAmount($cartId))->toBe(21.50); // Use new method with cartId

    // Set exempt and check again
    Cart::setVatExempt($cartId, true); // Pass cartId
    expect(Cart::taxAmount($cartId))->toBe(0.00); // Use new method with cartId
});

test('calculates zero tax when no tax zone is set', function () {
    $cart = Cart::create(); // No taxZone set
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));

    expect(Cart::taxAmount($cartId))->toBe(0.00); // Use new method with cartId
});

test('calculates tax based on specified tax zone', function () {
    $cart = Cart::create(taxZone: 'EU'); // Use EU zone (21%)
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));

    // Tax = 100.00 * 0.21 = 21.00
    expect(Cart::taxAmount($cartId))->toBe(21.00); // Use new method with cartId
});

// Note: Tax on shipping is implicitly tested in ShippingTest.php when checking totals.
// Note: Tax on extra costs is implicitly tested in CalculationsTest.php when checking totals.
