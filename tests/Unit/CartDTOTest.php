<?php

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;

test('can calculate total with discounts and tax', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Test Product',
                price: 100.00,
                quantity: 1
            ),
        ],
        discounts: [
            new DiscountDTO(
                code: 'TEST10',
                type: 'percentage',
                value: 10
            ),
        ],
        taxZone: 'US'
    );

    // 100 - 10% discount + 7.25% tax
    expect($cart->calculateTotal())->toBe(97.25);
});

test('can handle multiple discounts', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(
                id: '1',
                name: 'Test Product',
                price: 100.00,
                quantity: 1
            ),
        ],
        discounts: [
            new DiscountDTO(code: 'FIXED10', type: 'fixed', value: 10),
            new DiscountDTO(code: 'PERCENT10', type: 'percentage', value: 10),
        ]
    );

    // 100 - 10 (fixed) - 9 (10% of 90) = 81
    expect($cart->calculateTotal())->toBe(81.00);
});

test('empty cart returns zero total', function () {
    $cart = new CartDTO;

    expect($cart->calculateTotal())->toBe(0.0)
        ->and($cart->getItems()->isEmpty())->toBeTrue();
});

test('can get item count with multiple quantities', function () {
    $cart = new CartDTO(
        items: [
            new CartItemDTO(id: '1', name: 'Product 1', price: 10.00, quantity: 2),
            new CartItemDTO(id: '2', name: 'Product 2', price: 20.00, quantity: 3),
        ]
    );

    expect($cart->getItemCount())->toBe(5)
        ->and($cart->getItems())->toHaveCount(2);
});

test('calculates correct tax for mixed cart with VAT exempt items', function () {
    $cart = new CartDTO(taxZone: 'RO');

    // Regular item
    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Regular',
        price: 100.00,
        quantity: 1
    ));

    // Reduced VAT item
    $cart->addItem(new CartItemDTO(
        id: '2',
        name: 'Book',
        price: 100.00,
        quantity: 1,
        category: 'books'
    ));

    $normalTax = $cart->getTaxAmount();
    $cart->setVatExempt(true);
    $exemptTax = $cart->getTaxAmount();

    expect($normalTax)->toBe(24.00) // 19% + 5%
        ->and($exemptTax)->toBe(0.0);
});

test('handles complex cart calculations correctly', function () {
    $cart = new CartDTO(taxZone: 'RO');

    $cart->addItem(new CartItemDTO(
        id: '1',
        price: 1000.00,
        quantity: 1,
        name: 'Regular Item',
        metadata: []
    ));

    $cart->addItem(new CartItemDTO(
        id: '2',
        price: 50.00,
        quantity: 2,
        name: 'Book',
        category: 'books',
        metadata: []
    ));

    // Add shipping -- assume free shipping for orders over 1000
    $cart->setShippingMethod('express', [
        'amount' => 15.99,
        'vat_included' => true,
        'vat_rate' => 0.19,
    ]);

    // Add extra cost
    $cart->addExtraCost(new ExtraCostDTO(
        name: 'Gift Wrap',
        amount: 5.00
    ));

    expect($cart->getSubtotal())->toBe(1100.00)
        ->and($cart->getTaxAmount())->toBe(195.95)
        ->and($cart->calculateTotal())->toBe(1300.95);
});

test('throws exception when adding invalid quantity', function () {
    $cart = new CartDTO;

    expect(fn () => $cart->updateItemQuantity('1', -1))
        ->toThrow(InvalidArgumentException::class, 'Quantity must be positive');
});

test('handles rounding correctly for tax calculations', function () {
    $cart = new CartDTO(taxZone: 'RO');

    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Item',
        price: 33.33,
        quantity: 3,
        metadata: []
    ));

    expect($cart->getTaxAmount())->toBe(19.00); // 19% of 99.99
});

test('maintains precision in calculations', function () {
    $cart = new CartDTO(taxZone: 'RO');

    for ($i = 0; $i < 10; $i++) {
        $cart->addItem(new CartItemDTO(
            id: (string) $i,
            name: "Item $i",
            price: 9.99,
            quantity: 1,
            metadata: []
        ));
    }

    expect($cart->getSubtotal())->toBe(99.90)
        ->and($cart->getTaxAmount())->toBe(18.98); // 19% of 99.90
});
