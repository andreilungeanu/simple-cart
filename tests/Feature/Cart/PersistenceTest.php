<?php

namespace Tests\Feature\Cart;

use AndreiLungeanu\SimpleCart\Cart\Facades\SimpleCart as Cart; // Updated Facade namespace
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO; // Updated DTO namespace
use AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO; // Updated DTO namespace
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO; // Updated DTO namespace
use AndreiLungeanu\SimpleCart\Cart\Exceptions\CartException; // Updated Exception namespace
use AndreiLungeanu\SimpleCart\CartInstance; // Add use statement
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


test('can create, modify, save, and retrieve a cart via fluent wrapper', function () {
    $cartWrapper = Cart::create(userId: 'user-123', taxZone: 'RO');
    $cartId = $cartWrapper->getId();
    expect($cartId)->toBeString();

    $cartWrapper->addItem(new CartItemDTO(id: 'prod-abc', name: 'Test Product prod-abc', quantity: 2, price: 50.00))
        ->addNote('Persistence test note')
        ->applyDiscount(new DiscountDTO(code: 'SAVE10', type: 'fixed', value: 10.0))
        ->setShippingMethod('standard', ['vat_included' => false])
        ->setVatExempt(false);

    $loadedCartWrapper = Cart::find($cartId);
    $loadedCart = $loadedCartWrapper->getInstance();

    expect($loadedCart)->toBeInstanceOf(CartInstance::class) // Use imported class
        ->and($loadedCart->getId())->toBe($cartId)
        ->and($loadedCart->getUserId())->toBe('user-123')
        ->and($loadedCart->getTaxZone())->toBe('RO')
        ->and($loadedCart->getShippingMethod())->toBe('standard')
        ->and($loadedCart->isVatExempt())->toBeFalse()
        ->and($loadedCart->getItems())->toHaveCount(1)
        ->and($loadedCart->getItems()->first()->id)->toBe('prod-abc')
        ->and($loadedCart->getItems()->first()->quantity)->toBe(2)
        ->and($loadedCart->getNotes())->toHaveCount(1)
        ->and($loadedCart->getNotes()->first())->toBe('Persistence test note')
        ->and($loadedCart->getDiscounts())->toHaveCount(1)
        ->and($loadedCart->getDiscounts()->first()->code)->toBe('SAVE10');
});

test('fluent wrapper methods update an existing cart', function () {
    $cartWrapper = Cart::create(userId: 'user-xyz');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-initial', name: 'Test Product item-initial', quantity: 1, price: 10.00));

    $cartWrapper->addItem(new CartItemDTO(id: 'item-added', name: 'Test Product item-added', quantity: 1, price: 20.00))
        ->updateQuantity('item-initial', 5);

    $loadedCart = $cartWrapper->getInstance();
    $cartId = $cartWrapper->getId();

    expect($loadedCart->getId())->toBe($cartId)
        ->and($loadedCart->getUserId())->toBe('user-xyz')
        ->and($loadedCart->getItems())->toHaveCount(2);

    $items = $loadedCart->getItems()->keyBy('id');
    expect($items['item-initial']->quantity)->toBe(5)
        ->and($items['item-added']->quantity)->toBe(1);
});

test('findOrFail throws exception for non-existent cart', function () {
    $nonExistentId = \Illuminate\Support\Str::uuid()->toString();

    expect(fn() => Cart::findOrFail($nonExistentId))
        ->toThrow(CartException::class, "Cart with ID [{$nonExistentId}] not found.");
});

test('find returns null for non-existent cart', function () {
    $nonExistentId = \Illuminate\Support\Str::uuid()->toString();

    $result = Cart::find($nonExistentId);
    expect($result)->toBeNull();
});
