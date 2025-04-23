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
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)); // No category

    // Tax = 100.00 * 0.19 = 19.00
    expect(Cart::getTaxAmount())->toBe(19.00);
});

test('applies category specific tax rates', function () {
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00, category: 'books')); // Books category

    // Tax = 100.00 * 0.05 = 5.00
    expect(Cart::getTaxAmount())->toBe(5.00);
});

test('calculates correct tax for mixed category cart', function () {
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)) // Regular item, Tax = 19.00
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 100.00, category: 'books')); // Book item, Tax = 5.00

    // Total Tax = 19.00 + 5.00 = 24.00
    expect(Cart::getTaxAmount())->toBe(24.00);
});

test('applies different VAT rates by category correctly', function () {
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)) // Regular item, Tax = 19.00
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 100.00, category: 'books')) // Book item, Tax = 5.00
        ->addItem(new CartItemDTO(id: 'item-3', name: 'Test Product item-3', quantity: 1, price: 100.00, category: 'food')); // Food item, Tax = 9.00

    // Total Tax = 19.00 + 5.00 + 9.00 = 33.00
    expect(Cart::getTaxAmount())->toBe(33.00);
});


test('handles VAT exemption correctly', function () {
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)) // Regular item
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 50.00, category: 'books')); // Book item

    // Normal Tax = (100 * 0.19) + (50 * 0.05) = 19.00 + 2.50 = 21.50
    expect(Cart::getTaxAmount())->toBe(21.50);

    // Set exempt and check again
    Cart::setVatExempt(true);
    expect(Cart::getTaxAmount())->toBe(0.00);
});

test('calculates zero tax when no tax zone is set', function () {
    Cart::create() // No taxZone set
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));

    expect(Cart::getTaxAmount())->toBe(0.00);
});

test('calculates tax based on specified tax zone', function () {
    Cart::create(taxZone: 'EU') // Use EU zone (21%)
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));

    // Tax = 100.00 * 0.21 = 21.00
    expect(Cart::getTaxAmount())->toBe(21.00);
});

// Note: Tax on shipping is implicitly tested in ShippingTest.php when checking totals.
// Note: Tax on extra costs is implicitly tested in CalculationsTest.php when checking totals.
