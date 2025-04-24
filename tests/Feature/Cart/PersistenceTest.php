<?php

namespace Tests\Feature\Cart;

// Use the Facade for testing the public API
use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO; // Add if testing discounts persistence
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO; // Add if testing extra costs persistence
use AndreiLungeanu\SimpleCart\Exceptions\CartException; // Import CartException
use Illuminate\Foundation\Testing\RefreshDatabase; // Use RefreshDatabase for persistence tests

uses(RefreshDatabase::class); // Apply trait to tests in this file

// Removed duplicate helper function createTestItem - now defined in tests/Pest.php


test('can create, modify, save, and retrieve a cart via fluent wrapper', function () { // Renamed test
    // Arrange: Create a cart using the manager (Facade), returns wrapper
    $cartWrapper = Cart::create(userId: 'user-123', taxZone: 'RO');
    $cartId = $cartWrapper->getId();
    expect($cartId)->toBeString();

    // Act: Modify the cart using fluent methods on the wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'prod-abc', name: 'Test Product prod-abc', quantity: 2, price: 50.00))
        ->addNote('Persistence test note')
        ->applyDiscount(new DiscountDTO(code: 'SAVE10', type: 'fixed', value: 10.0))
        ->setShippingMethod('standard', ['vat_included' => false])
        ->setVatExempt(false); // Example: Explicitly set VAT status

    // Retrieve the cart instance again using the manager/wrapper
    $loadedCartWrapper = Cart::find($cartId); // Use find via Facade, returns wrapper
    $loadedCart = $loadedCartWrapper->getInstance(); // Get the underlying instance

    // Assert: Check if the loaded cart state matches the saved state
    expect($loadedCart)->toBeInstanceOf(\AndreiLungeanu\SimpleCart\CartInstance::class)
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

test('fluent wrapper methods update an existing cart', function () { // Renamed test
    // Arrange: Create a cart and add an initial item using the wrapper
    $cartWrapper = Cart::create(userId: 'user-xyz');
    $cartWrapper->addItem(new CartItemDTO(id: 'item-initial', name: 'Test Product item-initial', quantity: 1, price: 10.00));

    // Act: Use fluent methods on the wrapper to modify the cart
    $cartWrapper->addItem(new CartItemDTO(id: 'item-added', name: 'Test Product item-added', quantity: 1, price: 20.00))
        ->updateQuantity('item-initial', 5);

    // Assert: Find the cart again and check the updated state
    $loadedCart = $cartWrapper->getInstance(); // Get instance from the same wrapper
    $cartId = $cartWrapper->getId(); // Get the ID from the wrapper

    expect($loadedCart->getId())->toBe($cartId)
        ->and($loadedCart->getUserId())->toBe('user-xyz')
        ->and($loadedCart->getItems())->toHaveCount(2); // Should still have 2 distinct items

    // Check quantities
    $items = $loadedCart->getItems()->keyBy('id');
    expect($items['item-initial']->quantity)->toBe(5)
        ->and($items['item-added']->quantity)->toBe(1);
});

test('findOrFail throws exception for non-existent cart', function () {
    // Use a UUID that is unlikely to exist
    $nonExistentId = \Illuminate\Support\Str::uuid()->toString();

    // Expect an exception when trying to findOrFail via Facade
    // findOrFail now returns a FluentCart wrapper, but the exception logic remains
    expect(fn() => Cart::findOrFail($nonExistentId))
        ->toThrow(CartException::class, "Cart with ID [{$nonExistentId}] not found.");
});

test('find returns null for non-existent cart', function () {
    // Use a UUID that is unlikely to exist
    $nonExistentId = \Illuminate\Support\Str::uuid()->toString();

    // Expect null when trying to find via Facade
    $result = Cart::find($nonExistentId); // find now returns FluentCart|null
    expect($result)->toBeNull();
});
