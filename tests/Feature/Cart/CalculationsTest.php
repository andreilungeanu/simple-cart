<?php

use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;

test('calculates subtotal correctly', function () {
    $cartWrapper = Cart::create();
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 2, price: 10.00))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 5.50));

    expect(Cart::subtotal($cartWrapper->getId()))->toBe(25.50);
});

test('calculates total correctly with items only', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);

    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));

    $cartId = $cartWrapper->getId();
    expect(Cart::subtotal($cartId))->toBe(100.00)
        ->and(Cart::taxAmount($cartId))->toBe(19.00)
        ->and(Cart::total($cartId))->toBe(119.00);
});


test('maintains precision in subtotal calculations', function () {
    $cartWrapper = Cart::create();

    for ($i = 0; $i < 10; $i++) {
        $cartWrapper->addItem(new CartItemDTO(id: (string) $i, name: 'Test Product ' . (string) $i, quantity: 1, price: 9.99));
    }

    expect(Cart::subtotal($cartWrapper->getId()))->toBe(99.90);
});

test('maintains precision in tax calculations', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    $cartWrapper = Cart::create(taxZone: 'RO');

    for ($i = 0; $i < 10; $i++) {
        $cartWrapper->addItem(new CartItemDTO(id: (string) $i, name: 'Test Product ' . (string) $i, quantity: 1, price: 9.99));
    }
    expect(Cart::taxAmount($cartWrapper->getId()))->toBe(18.98);
});


test('handles rounding correctly for tax calculations', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 3, price: 33.33));

    expect(Cart::taxAmount($cartWrapper->getId()))->toBe(19.00);
});


test('calculates total with fixed extra costs', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00))
        ->addExtraCost(new ExtraCostDTO(name: 'Gift Wrap', amount: 5.00));

    $cartId = $cartWrapper->getId();

    expect(Cart::extraCostsTotal($cartId))->toBe(5.00)
        ->and(Cart::taxAmount($cartId))->toBe(19.00 + 0.95)
        ->and(Cart::total($cartId))->toBe(124.95);
});


test('calculates total with percentage-based extra costs', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00))
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 10, type: 'percentage'));

    $cartId = $cartWrapper->getId();

    expect(Cart::extraCostsTotal($cartId))->toBe(10.00)
        ->and(Cart::taxAmount($cartId))->toBe(19.00 + 1.90)
        ->and(Cart::total($cartId))->toBe(130.90);
});

test('maintains precision with percentage-based extra costs', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 99.99))
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 5, type: 'percentage'));

    $cartId = $cartWrapper->getId();

    expect(Cart::extraCostsTotal($cartId))->toBe(5.00)
        ->and(Cart::taxAmount($cartId))->toBe(19.00 + 0.95)
        ->and(Cart::total($cartId))->toBe(124.94);
});

test('calculates total with multiple extra costs', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00))
        ->addExtraCost(new ExtraCostDTO(name: 'Gift Wrap', amount: 5.00))
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 10, type: 'percentage'));

    $cartId = $cartWrapper->getId();

    expect(Cart::extraCostsTotal($cartId))->toBe(15.00)
        ->and(Cart::taxAmount($cartId))->toBe(19.00 + 2.85)
        ->and(Cart::total($cartId))->toBe(136.85);
});

test('extra costs are not taxed when cart is vat exempt', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00))
        ->addExtraCost(new ExtraCostDTO(name: 'Gift Wrap', amount: 5.00))
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 10, type: 'percentage'))
        ->setVatExempt(true);

    $cartId = $cartWrapper->getId();

    expect(Cart::extraCostsTotal($cartId))->toBe(15.00)
        ->and(Cart::taxAmount($cartId))->toBe(0.00)
        ->and(Cart::total($cartId))->toBe(115.00);
});

test('can remove an extra cost', function () {
    $cartWrapper = Cart::create();
    $cartId = $cartWrapper->getId();

    $cartWrapper->addExtraCost(new ExtraCostDTO(name: 'Gift Wrap', amount: 5.00))
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 10.00));

    expect(Cart::extraCostsTotal($cartId))->toBe(15.00);

    // Remove 'Gift Wrap' using the fluent wrapper
    $cartWrapper->removeExtraCost('Gift Wrap');

    // Check totals and remaining costs
    expect(Cart::extraCostsTotal($cartId))->toBe(10.00); // Only Handling remains
    $loadedCart = $cartWrapper->getInstance();
    expect($loadedCart->getExtraCosts())->toHaveCount(1)
        ->and($loadedCart->getExtraCosts()->first()->name)->toBe('Handling');

    // Remove the other cost
    $cartWrapper->removeExtraCost('Handling');
    expect(Cart::extraCostsTotal($cartId))->toBe(0.00);
    $loadedCartAfter = $cartWrapper->getInstance();
    expect($loadedCartAfter->getExtraCosts())->toBeEmpty();
});
