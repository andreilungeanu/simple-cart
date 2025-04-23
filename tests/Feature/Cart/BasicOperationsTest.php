<?php

// Use the Facade for testing the public API
use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use Illuminate\Support\Facades\Event;
use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Events\CartCleared;

// Removed duplicate helper function createTestItem - now defined in tests/Pest.php

test('can create a new cart instance via facade', function () {
    Event::fake();

    // Create implicitly returns the instance, but we test state via get()
    Cart::create();
    $cartData = Cart::get();

    expect($cartData)->toBeArray()
        ->and($cartData['id'])->toBeString()
        ->and($cartData['items'])->toBeEmpty();

    Event::assertDispatched(CartCreated::class);
});

test('can add an item to the cart', function () {
    Event::fake();

    Cart::create()->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1));
    $cartData = Cart::get();

    expect($cartData['items'])->toHaveCount(1)
        ->and($cartData['items'][0]['id'])->toBe('item-1')
        ->and($cartData['items'][0]['quantity'])->toBe(1);

    // Should dispatch Created then Updated
    Event::assertDispatchedTimes(CartCreated::class, 1);
    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can add multiple items', function () {
    Cart::create()
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 99.99, quantity: 2));

    $cartData = Cart::get();

    expect($cartData['items'])->toHaveCount(2)
        ->and($cartData['items'][0]['id'])->toBe('item-1')
        ->and($cartData['items'][1]['id'])->toBe('item-2')
        ->and($cartData['items'][1]['quantity'])->toBe(2);
});


test('can update item quantity', function () {
    Event::fake();

    Cart::create()
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->updateQuantity('item-1', 3);

    $cartData = Cart::get();

    expect($cartData['items'])->toHaveCount(1)
        ->and($cartData['items'][0]['id'])->toBe('item-1')
        ->and($cartData['items'][0]['quantity'])->toBe(3);

    // Created, Updated (add), Updated (update)
    Event::assertDispatchedTimes(CartUpdated::class, 2);
});

test('throws exception when updating quantity for non-existent item', function () {
    Cart::create()->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1));

    expect(fn() => Cart::updateQuantity('non-existent-item', 2))
        ->toThrow(CartException::class, 'Item with ID non-existent-item not found in cart.');
});

test('throws exception when updating quantity to zero or less', function () {
    Cart::create()->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1));

    expect(fn() => Cart::updateQuantity('item-1', 0))
        ->toThrow(InvalidArgumentException::class, 'Quantity must be positive');

    expect(fn() => Cart::updateQuantity('item-1', -1))
        ->toThrow(InvalidArgumentException::class, 'Quantity must be positive');
});


test('can get item count', function () {
    Cart::create()
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 2))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 99.99, quantity: 3));

    expect(Cart::getItemCount())->toBe(5);
});

test('can clear the cart', function () {
    Event::fake();

    Cart::create()
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 99.99, quantity: 1));

    expect(Cart::get()['items'])->toHaveCount(2);

    Cart::clear();

    expect(Cart::get()['items'])->toBeEmpty()
        ->and(Cart::getItemCount())->toBe(0);

    // Created, Updated(x2), Cleared
    Event::assertDispatched(CartCleared::class);
});

// Add test for removeItem once implemented
test('can remove an item from the cart', function () {
    Event::fake();

    Cart::create()
        ->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 50.00, quantity: 2)); // Qty 2

    expect(Cart::getItemCount())->toBe(3); // 1 + 2 = 3

    Cart::removeItem('item-1'); // Remove the first item

    $cartData = Cart::get();
    expect($cartData['items'])->toHaveCount(1) // Only item-2 should remain
        ->and($cartData['items'][0]['id'])->toBe('item-2')
        ->and(Cart::getItemCount())->toBe(2); // Quantity of item-2

    // Create(1) + Add(1) + Add(1) + Remove(1) = 3 CartUpdated events
    Event::assertDispatchedTimes(CartUpdated::class, 3);
});

test('can add a note', function () {
    Event::fake();

    Cart::create()->addNote('This is a test note.');
    $cartData = Cart::get();

    expect($cartData['notes'])->toBeArray()
        ->and($cartData['notes'])->toHaveCount(1)
        ->and($cartData['notes'][0])->toBe('This is a test note.');

    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can apply a discount code', function () {
    Event::fake();

    Cart::create()->applyDiscount('TESTCODE');
    $cartData = Cart::get();

    expect($cartData['discounts'])->toBeArray()
        ->and($cartData['discounts'])->toHaveCount(1)
        ->and($cartData['discounts'][0]['code'])->toBe('TESTCODE'); // Assuming DiscountDTO structure

    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can set vat exempt status', function () {
    Event::fake();

    Cart::create()->setVatExempt(true);
    $cartData = Cart::get();
    expect($cartData['vat_exempt'])->toBeTrue();

    Cart::setVatExempt(false);
    $cartData = Cart::get();
    expect($cartData['vat_exempt'])->toBeFalse();

    Event::assertDispatchedTimes(CartUpdated::class, 2);
});

test('can set shipping method', function () {
    Event::fake();
    $shippingInfo = ['vat_rate' => 0.19, 'vat_included' => false];

    Cart::create()->setShippingMethod('standard', $shippingInfo);
    $cartData = Cart::get();

    expect($cartData['shipping_method'])->toBe('standard');
    // We might also want to check internal state if possible/needed, or rely on calculation tests
    // expect(Cart::getShippingVatInfo()['rate'])->toBe(0.19);

    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can chain methods fluently', function () {
    Event::fake();

    Cart::create()
        ->addItem(new CartItemDTO(id: 'item-A', name: 'Test Product item-A', price: 10.00, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-B', name: 'Test Product item-B', price: 20.00, quantity: 2))
        ->updateQuantity('item-A', 3) // 3 * 10 = 30
        ->applyDiscount('SUMMER10')    // Assuming DiscountDTO defaults
        ->addNote('Please deliver quickly')
        ->setShippingMethod('express', ['vat_rate' => 0.1]) // Assuming express costs something
        ->setVatExempt(false);

    $cartData = Cart::get();

    expect($cartData['items'])->toHaveCount(2)
        ->and($cartData['items'][0]['quantity'])->toBe(3)
        ->and($cartData['discounts'])->toHaveCount(1)
        ->and($cartData['notes'])->toHaveCount(1)
        ->and($cartData['shipping_method'])->toBe('express')
        ->and($cartData['vat_exempt'])->toBeFalse();

    // Check counts of events
    Event::assertDispatched(CartCreated::class);
    // 7 updates: addItem, addItem, updateQuantity, applyDiscount, addNote, setShippingMethod, setVatExempt
    Event::assertDispatchedTimes(CartUpdated::class, 7);
});
