<?php

use AndreiLungeanu\SimpleCart\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;

test('can create a new cart', function () {
    $cart = app(SimpleCart::class)->create();

    expect($cart->get())->toBeInstanceOf(\AndreiLungeanu\SimpleCart\DTOs\CartDTO::class);
});

test('can add items to cart', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 99.99,
            quantity: 1
        ));

    expect($cart->get()->getItems())->toHaveCount(1);
});

test('can update item quantity', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 99.99,
            quantity: 1
        ))
        ->updateQuantity('1', 2);

    expect($cart->get()->getItems()->first()->quantity)->toBe(2);
});

test('can calculate shipping costs', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 50.00, // Below free shipping threshold
            quantity: 1
        ));

    $cartDTO = $cart->get();
    expect($cartDTO->getShippingCost())->toBe(0.0);

    $cartWithShipping = new CartDTO(
        items: $cartDTO->getItems()->toArray(),
        shippingMethod: 'standard',
        taxZone: 'RO'
    );

    expect($cartWithShipping->getShippingAmount())->toBe(5.99);
});

test('can get item count', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 100.00,
            quantity: 2
        ));

    expect($cart->get()->getItemCount())->toBe(2);
});

test('can add extra costs to cart', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 100.00,
            quantity: 1
        ));

    $cart->get()->addExtraCost(new ExtraCostDTO(
        name: 'Gift Wrapping',
        amount: 5.00
    ));

    expect($cart->total())->toBe(105.00);
});

test('can calculate percentage based extra costs', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 100.00,
            quantity: 1
        ));

    $cart->get()->addExtraCost(new ExtraCostDTO(
        name: 'Handling Fee',
        amount: 10,
        type: 'percentage'
    ));

    expect($cart->total())->toBe(110.00);
});

test('throws exception when accessing empty cart', function () {
    $cart = app(SimpleCart::class);

    expect(fn() => $cart->updateQuantity('1', 2))
        ->toThrow(CartException::class, 'Cart not found');
});

test('can save and retrieve cart', function () {
    $cart = app(SimpleCart::class)
        ->create()
        ->addItem(new CartItemDTO(
            id: '1',
            name: 'Test Product',
            price: 100.00,
            quantity: 1
        ))
        ->save();

    $cartId = $cart->get()->id;

    $loadedCart = app(SimpleCart::class)->find($cartId);
    expect($loadedCart->get()->getItems())->toHaveCount(1);
});
