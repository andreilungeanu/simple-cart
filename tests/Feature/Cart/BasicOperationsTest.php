<?php

use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use Illuminate\Support\Facades\Event;
use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Events\CartCleared;

test('can create a new cart instance via facade', function () {
    Event::fake();

    $cartWrapper = Cart::create();

    expect($cartWrapper)->toBeInstanceOf(\AndreiLungeanu\SimpleCart\FluentCart::class)
        ->and($cartWrapper->getId())->toBeString();

    $cartInstance = $cartWrapper->getInstance();
    expect($cartInstance)->toBeInstanceOf(\AndreiLungeanu\SimpleCart\CartInstance::class)
        ->and($cartInstance->getItems())->toBeEmpty();

    Event::assertDispatched(CartCreated::class, function ($event) use ($cartWrapper) {
        return $event->cart instanceof \AndreiLungeanu\SimpleCart\CartInstance
            && $event->cart->getId() === $cartWrapper->getId();
    });
});

test('can add an item to the cart', function () {
    Event::fake();

    $cartWrapper = Cart::create();
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1));

    $loadedCart = $cartWrapper->getInstance();

    expect($loadedCart->getItems())->toHaveCount(1)
        ->and($loadedCart->getItems()->first()->id)->toBe('item-1')
        ->and($loadedCart->getItems()->first()->quantity)->toBe(1);

    Event::assertDispatchedTimes(CartCreated::class, 1);
    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can add multiple items', function () {
    $cartWrapper = Cart::create();
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 99.99, quantity: 2));

    $loadedCart = $cartWrapper->getInstance();

    $items = $loadedCart->getItems()->keyBy('id');
    expect($items)->toHaveCount(2)
        ->and($items['item-1']->quantity)->toBe(1)
        ->and($items['item-2']->quantity)->toBe(2);
});


test('can update item quantity', function () {
    Event::fake();

    $cartWrapper = Cart::create();
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->updateQuantity('item-1', 3);

    $loadedCart = $cartWrapper->getInstance();

    expect($loadedCart->getItems())->toHaveCount(1)
        ->and($loadedCart->getItems()->first()->id)->toBe('item-1')
        ->and($loadedCart->getItems()->first()->quantity)->toBe(3);

    Event::assertDispatchedTimes(CartUpdated::class, 2);
});

test('throws exception when updating quantity for non-existent item', function () {
    $cartWrapper = Cart::create();
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1));

    expect(fn() => $cartWrapper->updateQuantity('non-existent-item', 2))
        ->toThrow(CartException::class);
});

test('throws exception when updating quantity to zero or less', function () {
    $cartWrapper = Cart::create();
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1));

    expect(fn() => $cartWrapper->updateQuantity('item-1', 0))
        ->toThrow(InvalidArgumentException::class, 'Quantity must be positive');

    expect(fn() => $cartWrapper->updateQuantity('item-1', -1))
        ->toThrow(InvalidArgumentException::class, 'Quantity must be positive');
});


test('can get item count', function () {
    $cartWrapper = Cart::create();
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 2))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 99.99, quantity: 3));

    expect(Cart::itemCount($cartWrapper->getId()))->toBe(5);
});

test('can clear the cart', function () {
    Event::fake();

    $cartWrapper = Cart::create();
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 99.99, quantity: 1));

    $loadedCart = $cartWrapper->getInstance();
    expect($loadedCart->getItems())->toHaveCount(2);

    $cartWrapper->clear();

    $clearedCart = $cartWrapper->getInstance();
    expect($clearedCart->getItems())->toBeEmpty()
        ->and(Cart::itemCount($cartWrapper->getId()))->toBe(0);

    Event::assertDispatched(CartCleared::class, function ($event) use ($cartWrapper) {
        return $event->cartId === $cartWrapper->getId();
    });
});

test('can remove an item from the cart', function () {
    Event::fake();

    $cartWrapper = Cart::create();
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 50.00, quantity: 2));

    $cartId = $cartWrapper->getId();
    expect(Cart::itemCount($cartId))->toBe(3);

    $cartWrapper->removeItem('item-1');

    $loadedCart = $cartWrapper->getInstance();
    expect($loadedCart->getItems())->toHaveCount(1)
        ->and($loadedCart->getItems()->first()->id)->toBe('item-2')
        ->and(Cart::itemCount($cartId))->toBe(2);

    Event::assertDispatchedTimes(CartUpdated::class, 3);
});

test('can add a note', function () {
    Event::fake();

    $cartWrapper = Cart::create();
    $cartWrapper->addNote('This is a test note.');

    $loadedCart = $cartWrapper->getInstance();

    expect($loadedCart->getNotes())->toBeCollection()
        ->and($loadedCart->getNotes())->toHaveCount(1)
        ->and($loadedCart->getNotes()->first())->toBe('This is a test note.');

    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can apply a discount code', function () {
    Event::fake();
    $discount = new DiscountDTO(code: 'TESTCODE', type: 'fixed', value: 5.0);

    $cartWrapper = Cart::create();
    $cartWrapper->applyDiscount($discount);

    $loadedCart = $cartWrapper->getInstance();

    expect($loadedCart->getDiscounts())->toBeCollection()
        ->and($loadedCart->getDiscounts())->toHaveCount(1)
        ->and($loadedCart->getDiscounts()->first()->code)->toBe('TESTCODE');

    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can set vat exempt status', function () {
    Event::fake();

    $cartWrapper = Cart::create();
    $cartWrapper->setVatExempt(true);
    $loadedCart1 = $cartWrapper->getInstance();
    expect($loadedCart1->isVatExempt())->toBeTrue();

    $cartWrapper->setVatExempt(false);
    $loadedCart2 = $cartWrapper->getInstance();
    expect($loadedCart2->isVatExempt())->toBeFalse();

    Event::assertDispatchedTimes(CartUpdated::class, 2);
});

test('can set shipping method', function () {
    Event::fake();
    $shippingInfo = ['vat_rate' => 0.19, 'vat_included' => false];

    $cartWrapper = Cart::create();
    $cartWrapper->setShippingMethod('standard', $shippingInfo);

    $loadedCart = $cartWrapper->getInstance();

    expect($loadedCart->getShippingMethod())->toBe('standard');
    expect($loadedCart->getShippingVatInfo()['rate'])->toBe(0.19);

    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can chain methods fluently via wrapper', function () {
    Event::fake();

    $cartWrapper = Cart::create();

    $cartWrapper->addItem(new CartItemDTO(id: 'item-A', name: 'Test Product item-A', price: 10.00, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-B', name: 'Test Product item-B', price: 20.00, quantity: 2))
        ->updateQuantity('item-A', 3)
        ->applyDiscount(new DiscountDTO(code: 'SUMMER10', type: 'percentage', value: 10.0))
        ->addNote('Please deliver quickly')
        ->setShippingMethod('express', ['vat_rate' => 0.1])
        ->setVatExempt(false);

    $loadedCart = $cartWrapper->getInstance();

    $items = $loadedCart->getItems()->keyBy('id');
    expect($items)->toHaveCount(2)
        ->and($items['item-A']->quantity)->toBe(3)
        ->and($loadedCart->getDiscounts())->toHaveCount(1)
        ->and($loadedCart->getNotes())->toHaveCount(1)
        ->and($loadedCart->getShippingMethod())->toBe('express')
        ->and($loadedCart->isVatExempt())->toBeFalse();

    Event::assertDispatched(CartCreated::class);
    Event::assertDispatchedTimes(CartUpdated::class, 7);
});
