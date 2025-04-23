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


test('can save and retrieve a basic cart', function () {
    // Arrange: Create and save a cart
    Cart::create(userId: 'user-123', taxZone: 'RO')
        ->addItem(new CartItemDTO(id: 'prod-abc', name: 'Test Product prod-abc', quantity: 2, price: 50.00))
        ->addNote('Persistence test note')
        ->applyDiscount(new DiscountDTO(code: 'SAVE10', type: 'fixed', value: 10.0)) // Pass DTO with 'value'
        ->setShippingMethod('standard', ['vat_included' => false])
        ->save(); // Save the cart state

    // Retrieve the ID from the current cart state
    $cartDataBefore = Cart::get();
    $cartId = $cartDataBefore['id'];
    expect($cartId)->toBeString();

    // Act: Create a new cart instance (simulating a new request) and find the saved cart
    // We need to resolve a new instance, not use the existing singleton state
    $newCartInstance = app(\AndreiLungeanu\SimpleCart\SimpleCart::class);
    $loadedCart = $newCartInstance->find($cartId); // Use find on the instance

    // Assert: Check if the loaded cart state matches the saved state
    $cartDataAfter = $loadedCart->get(); // Get data from the loaded instance

    expect($cartDataAfter['id'])->toBe($cartId)
        ->and($cartDataAfter['user_id'])->toBe('user-123')
        ->and($cartDataAfter['tax_zone'])->toBe('RO')
        ->and($cartDataAfter['shipping_method'])->toBe('standard')
        ->and($cartDataAfter['items'])->toHaveCount(1)
        ->and($cartDataAfter['items'][0]['id'])->toBe('prod-abc')
        ->and($cartDataAfter['items'][0]['quantity'])->toBe(2)
        ->and($cartDataAfter['notes'])->toHaveCount(1)
        ->and($cartDataAfter['notes'][0])->toBe('Persistence test note')
        ->and($cartDataAfter['discounts'])->toHaveCount(1)
        ->and($cartDataAfter['discounts'][0]['code'])->toBe('SAVE10');
    // We don't assert calculated values (like total) as they aren't persisted
});

test('saving updates an existing cart', function () {
    // Arrange: Create and save a cart
    Cart::create(userId: 'user-xyz')
        ->addItem(new CartItemDTO(id: 'item-initial', name: 'Test Product item-initial', quantity: 1, price: 10.00))
        ->save();
    $cartId = Cart::get()['id'];

    // Act: Find the cart, modify it, and save again
    $cartInstance = app(\AndreiLungeanu\SimpleCart\SimpleCart::class)->find($cartId);
    $cartInstance
        ->addItem(new CartItemDTO(id: 'item-added', name: 'Test Product item-added', quantity: 1, price: 20.00))
        ->updateQuantity('item-initial', 5)
        ->save();

    // Assert: Find the cart again and check the updated state
    $newCartInstance = app(\AndreiLungeanu\SimpleCart\SimpleCart::class);
    $loadedCart = $newCartInstance->find($cartId);
    $cartDataAfter = $loadedCart->get();

    expect($cartDataAfter['id'])->toBe($cartId)
        ->and($cartDataAfter['user_id'])->toBe('user-xyz')
        ->and($cartDataAfter['items'])->toHaveCount(2)
        ->and($cartDataAfter['items'][0]['id'])->toBe('item-initial')
        ->and($cartDataAfter['items'][0]['quantity'])->toBe(5)
        ->and($cartDataAfter['items'][1]['id'])->toBe('item-added');
});

test('find throws exception for non-existent cart', function () {
    // Use a UUID that is unlikely to exist
    $nonExistentId = \Illuminate\Support\Str::uuid()->toString();

    // Expect an exception when trying to find
    expect(fn() => app(\AndreiLungeanu\SimpleCart\SimpleCart::class)->find($nonExistentId))
        ->toThrow(CartException::class, "Cart with ID {$nonExistentId} not found.");
});
