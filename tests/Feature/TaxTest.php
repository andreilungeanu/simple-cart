<?php

use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\Facades\SimpleCart as Cart;

beforeEach(function () {
    config([
        'simple-cart.tax.settings.zones' => [
            'RO' => [
                'default_rate' => 0.19,
                'rates_by_category' => [
                    'books' => 0.05,
                    'food' => 0.09,
                ],
                'apply_to_shipping' => true,
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
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));

    expect(Cart::taxAmount($cartId))->toBe(19.00);
});

test('applies category specific tax rates', function () {
    $cart = Cart::create(taxZone: 'RO');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00, category: 'books'));

    expect(Cart::taxAmount($cartId))->toBe(5.00);
});

test('calculates correct tax for mixed category cart', function () {
    $cart = Cart::create(taxZone: 'RO');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));
    Cart::addItem($cartId, new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 100.00, category: 'books'));

    expect(Cart::taxAmount($cartId))->toBe(24.00);
});

test('applies different VAT rates by category correctly', function () {
    $cart = Cart::create(taxZone: 'RO');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));
    Cart::addItem($cartId, new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 100.00, category: 'books'));
    Cart::addItem($cartId, new CartItemDTO(id: 'item-3', name: 'Test Product item-3', quantity: 1, price: 100.00, category: 'food'));

    expect(Cart::taxAmount($cartId))->toBe(33.00);
});

test('handles VAT exemption correctly', function () {
    $cart = Cart::create(taxZone: 'RO');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));
    Cart::addItem($cartId, new CartItemDTO(id: 'item-2', name: 'Test Product item-2', quantity: 1, price: 50.00, category: 'books'));

    expect(Cart::taxAmount($cartId))->toBe(21.50);

    Cart::setVatExempt($cartId, true);
    expect(Cart::taxAmount($cartId))->toBe(0.00);
});

test('calculates zero tax when no tax zone is set', function () {
    $cart = Cart::create();
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));

    expect(Cart::taxAmount($cartId))->toBe(0.00);
});

test('calculates tax based on specified tax zone', function () {
    $cart = Cart::create(taxZone: 'EU');
    $cartId = $cart->getId();
    Cart::addItem($cartId, new CartItemDTO(id: 'item-1', name: 'Test Product item-1', quantity: 1, price: 100.00));

    expect(Cart::taxAmount($cartId))->toBe(21.00);
});
