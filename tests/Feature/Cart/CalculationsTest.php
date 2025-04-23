<?php

// Use the Facade for testing the public API
use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;

// Removed duplicate helper function createTestItem - now defined in tests/Pest.php

test('calculates subtotal correctly', function () {
    Cart::create()
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 2, price: 10.00)) // 20.00
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 5.50));  //  5.50

    expect(Cart::getSubtotal())->toBe(25.50);
});

test('calculates total correctly with items only', function () {
    // Assuming default tax zone 'RO' (19%) and no shipping/discounts/extra costs yet
    // This might require setting up config or mocking providers if defaults aren't suitable
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]); // Ensure tax rate for test

    Cart::create(taxZone: 'RO') // Set tax zone on creation
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)); // Subtotal 100.00

    // Tax = 100.00 * 0.19 = 19.00
    // Total = 100.00 + 19.00 = 119.00
    expect(Cart::getSubtotal())->toBe(100.00)
        ->and(Cart::getTaxAmount())->toBe(19.00)
        ->and(Cart::total())->toBe(119.00);
});


test('maintains precision in subtotal calculations', function () {
    Cart::create(); // No tax zone needed for subtotal

    for ($i = 0; $i < 10; $i++) {
        Cart::addItem(new CartItemDTO(id: (string) $i, name: 'Test Product ' . (string) $i, quantity: 1, price: 9.99));
    }

    // 10 * 9.99 = 99.90
    expect(Cart::getSubtotal())->toBe(99.90);
});

test('maintains precision in tax calculations', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    Cart::create(taxZone: 'RO');

    for ($i = 0; $i < 10; $i++) {
        Cart::addItem(new CartItemDTO(id: (string) $i, name: 'Test Product ' . (string) $i, quantity: 1, price: 9.99));
    }
    // Subtotal = 99.90
    // Tax = 99.90 * 0.19 = 18.981 -> rounded to 18.98
    expect(Cart::getTaxAmount())->toBe(18.98);
});


test('handles rounding correctly for tax calculations', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 3, price: 33.33)); // Subtotal = 99.99

    // Tax = 99.99 * 0.19 = 18.9981 -> rounded to 19.00
    expect(Cart::getTaxAmount())->toBe(19.00);
});


// --- Extra Cost Calculations ---

test('calculates total with fixed extra costs', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]); // Assume 19% tax on items and extra costs
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)) // Subtotal 100.00, Item Tax 19.00
        ->addExtraCost(new ExtraCostDTO(name: 'Gift Wrap', amount: 5.00)); // Extra Cost 5.00

    // Extra Cost Tax = 5.00 * 0.19 = 0.95
    // Total = Subtotal + ItemTax + ExtraCost + ExtraCostTax
    // Total = 100.00 + 19.00 + 5.00 + 0.95 = 124.95

    expect(Cart::getExtraCostsTotal())->toBe(5.00)
        ->and(Cart::getTaxAmount())->toBe(19.00 + 0.95) // Item tax + Extra cost tax
        ->and(Cart::total())->toBe(124.95);
});


test('calculates total with percentage-based extra costs', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)) // Subtotal 100.00, Item Tax 19.00
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 10, type: 'percentage')); // Extra Cost = 10% of 100.00 = 10.00

    // Extra Cost Tax = 10.00 * 0.19 = 1.90
    // Total = Subtotal + ItemTax + ExtraCost + ExtraCostTax
    // Total = 100.00 + 19.00 + 10.00 + 1.90 = 130.90

    expect(Cart::getExtraCostsTotal())->toBe(10.00)
        ->and(Cart::getTaxAmount())->toBe(19.00 + 1.90) // Item tax + Extra cost tax
        ->and(Cart::total())->toBe(130.90);
});

test('maintains precision with percentage-based extra costs', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 99.99)) // Subtotal 99.99, Item Tax 19.00 (rounded from 18.9981)
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 5, type: 'percentage')); // Extra Cost = 5% of 99.99 = 4.9995 -> rounded to 5.00

    // Extra Cost Tax = 5.00 * 0.19 = 0.95
    // Total = Subtotal + ItemTax + ExtraCost + ExtraCostTax
    // Total = 99.99 + 19.00 + 5.00 + 0.95 = 124.94

    expect(Cart::getExtraCostsTotal())->toBe(5.00)
        ->and(Cart::getTaxAmount())->toBe(19.00 + 0.95) // Item tax + Extra cost tax
        ->and(Cart::total())->toBe(124.94);
});

test('calculates total with multiple extra costs', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    Cart::create(taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)) // Subtotal 100.00, Item Tax 19.00
        ->addExtraCost(new ExtraCostDTO(name: 'Gift Wrap', amount: 5.00)) // Fixed 5.00
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 10, type: 'percentage')); // Percentage 10.00

    // Total Extra Cost = 5.00 + 10.00 = 15.00
    // Extra Cost Tax = 15.00 * 0.19 = 2.85
    // Total = Subtotal + ItemTax + ExtraCostTotal + ExtraCostTaxTotal
    // Total = 100.00 + 19.00 + 15.00 + 2.85 = 136.85

    expect(Cart::getExtraCostsTotal())->toBe(15.00)
        ->and(Cart::getTaxAmount())->toBe(19.00 + 2.85) // Item tax + Extra cost tax
        ->and(Cart::total())->toBe(136.85);
});

test('extra costs are not taxed when cart is vat exempt', function () {
    Cart::create(taxZone: 'RO') // Tax zone needed for default rate lookup if cost has VAT
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)) // Subtotal 100.00
        ->addExtraCost(new ExtraCostDTO(name: 'Gift Wrap', amount: 5.00))
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 10, type: 'percentage'))
        ->setVatExempt(true); // Set exempt

    // Total = Subtotal + ExtraCostTotal
    // Total = 100.00 + 15.00 = 115.00

    expect(Cart::getExtraCostsTotal())->toBe(15.00)
        ->and(Cart::getTaxAmount())->toBe(0.00) // No tax
        ->and(Cart::total())->toBe(115.00);
});


// Add tests for Shipping and Discount calculations later in dedicated files or here if simple enough
