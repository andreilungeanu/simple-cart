<?php

use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Cart\Facades\SimpleCart;

test('extra cost with custom vatRate is taxed using its own rate', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    $cart = SimpleCart::create(taxZone: 'RO');
    $cart->addItem(new CartItemDTO(id: 'i1', name: 'P', price: 100.00, quantity: 1))
        ->addExtraCost([
            'name' => 'Insurance',
            'amount' => 10.00,
            'type' => 'fixed',
            'vatRate' => 0.05,
            'vatIncluded' => false,
        ]);

    $id = $cart->getId();

    expect(SimpleCart::taxAmount($id))->toBe(19.00 + 0.50)
        ->and(SimpleCart::total($id))->toBe(100.00 + 10.00 + 19.00 + 0.50);
});

test('extra cost with vatIncluded true does not add tax again', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    $cart = SimpleCart::create(taxZone: 'RO');
    $cart->addItem(new CartItemDTO(id: 'i1', name: 'P', price: 100.00, quantity: 1))
        ->addExtraCost([
            'name' => 'GiftWrapIncluded',
            'amount' => 11.90, // includes 19% VAT already
            'type' => 'fixed',
            'vatRate' => 0.19,
            'vatIncluded' => true,
        ]);

    $id = $cart->getId();

    // Item tax = 19.00; extra cost VAT already included so tax amount should be 19.00 only
    expect(SimpleCart::taxAmount($id))->toBe(19.00)
        ->and(SimpleCart::total($id))->toBe(100.00 + 11.90 + 19.00);
});

test('extra cost falls back to cart default vat when vatRate is null', function () {
    config(['simple-cart.tax.settings.zones.RO.default_rate' => 0.19]);
    $cart = SimpleCart::create(taxZone: 'RO');
    $cart->addItem(new CartItemDTO(id: 'i1', name: 'P', price: 200.00, quantity: 1))
        ->addExtraCost(new ExtraCostDTO(name: 'Handling', amount: 10, type: 'percentage'));

    $id = $cart->getId();

    // handling = 10% of 200 = 20.00; VAT on handling = 20 * 0.19 = 3.80
    expect(SimpleCart::extraCostsTotal($id))->toBe(20.00)
        ->and(SimpleCart::taxAmount($id))->toBe(200.00 * 0.19 + 3.80);
});
