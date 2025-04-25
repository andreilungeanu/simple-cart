<?php

use AndreiLungeanu\SimpleCart\Cart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;

beforeEach(function () {
    config([
        'simple-cart.shipping.settings.free_shipping_threshold' => 100.00,
        'simple-cart.shipping.settings.methods' => [
            'standard' => ['name' => 'Standard', 'cost' => 5.99, 'vat_rate' => 0.19],
            'express' => ['name' => 'Express', 'cost' => 15.99, 'vat_rate' => 0.19],
        ],
        'simple-cart.tax.settings.zones.RO.default_rate' => 0.19,
    ]);
});

test('applies shipping charge when below threshold', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 90.00))
        ->setShippingMethod('standard', ['vat_included' => false]);

    expect(Cart::shippingAmount($cartWrapper->getId()))->toBe(5.99);
});

test('applies free shipping at threshold', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00))
        ->setShippingMethod('standard', ['vat_included' => false]);

    expect(Cart::shippingAmount($cartWrapper->getId()))->toBe(0.00);
});

test('isFreeShippingApplied returns true when threshold met', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00)) // At threshold
        ->setShippingMethod('standard', ['vat_included' => false]);

    expect(Cart::isFreeShippingApplied($cartWrapper->getId()))->toBeTrue();
});

test('isFreeShippingApplied returns false when below threshold', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 90.00)) // Below threshold
        ->setShippingMethod('standard', ['vat_included' => false]);

    expect(Cart::isFreeShippingApplied($cartWrapper->getId()))->toBeFalse();
});

test('isFreeShippingApplied returns false when no shipping method set', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 150.00)); // Above threshold, but no method set

    expect(Cart::isFreeShippingApplied($cartWrapper->getId()))->toBeFalse();
});

test('applies free shipping above threshold', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 150.00))
        ->setShippingMethod('standard', ['vat_included' => false]);

    expect(Cart::shippingAmount($cartWrapper->getId()))->toBe(0.00);
});


test('respects custom shipping threshold', function () {
    config(['simple-cart.shipping.settings.free_shipping_threshold' => 200.00]);

    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 150.00))
        ->setShippingMethod('standard', ['vat_included' => false]);

    $cartId = $cartWrapper->getId();
    expect(Cart::shippingAmount($cartId))->toBe(5.99);

    $cartWrapper->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 50.00));

    expect(Cart::shippingAmount($cartId))->toBe(0.00)
        ->and(Cart::subtotal($cartId))->toBe(200.00);
});

test('calculates total with shipping cost and tax', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 90.00))
        ->setShippingMethod('standard', ['vat_included' => false]);

    $cartId = $cartWrapper->getId();

    expect(Cart::subtotal($cartId))->toBe(90.00)
        ->and(Cart::shippingAmount($cartId))->toBe(5.99)
        ->and(Cart::taxAmount($cartId))->toBe(18.24)
        ->and(Cart::total($cartId))->toBe(114.23);
});


test('handles shipping with included VAT correctly in total', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 90.00))
        ->setShippingMethod('express', ['vat_included' => true, 'vat_rate' => 0.19]);

    $cartId = $cartWrapper->getId();

    $shippingAmount = Cart::shippingAmount($cartId);
    $taxAmount = Cart::taxAmount($cartId);
    $total = Cart::total($cartId);

    expect($shippingAmount)->toBe(15.99)
        ->and($taxAmount)->toBe(17.10)
        ->and($total)->toBe(123.09);
});

test('handles VAT exemption with shipping', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 90.00))
        ->setShippingMethod('standard', ['vat_included' => false])
        ->setVatExempt(true);

    $cartId = $cartWrapper->getId();

    expect(Cart::shippingAmount($cartId))->toBe(5.99)
        ->and(Cart::taxAmount($cartId))->toBe(0.00)
        ->and(Cart::total($cartId))->toBe(95.99);
});

test('returns zero shipping if no method set', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 50.00));

    expect(Cart::shippingAmount($cartWrapper->getId()))->toBe(0.00);
});
