<?php

// Use the Facade for testing the public API
use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO; // Import DiscountDTO
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use Illuminate\Support\Facades\Event;
use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Events\CartCleared;

// Removed duplicate helper function createTestItem - now defined in tests/Pest.php

test('can create a new cart instance via facade', function () {
    Event::fake();

    $cartWrapper = Cart::create(); // create() returns FluentCart wrapper

    // Assertions should check the wrapper and potentially the underlying instance
    expect($cartWrapper)->toBeInstanceOf(\AndreiLungeanu\SimpleCart\FluentCart::class) // Check wrapper type
        ->and($cartWrapper->getId())->toBeString(); // Check ID via wrapper

    // Optionally check the underlying instance state if needed
    $cartInstance = $cartWrapper->getInstance();
    expect($cartInstance)->toBeInstanceOf(\AndreiLungeanu\SimpleCart\CartInstance::class)
        ->and($cartInstance->getItems())->toBeEmpty();

    // Update event assertion to use the wrapper's ID
    Event::assertDispatched(CartCreated::class, function ($event) use ($cartWrapper) {
        // The event carries the CartInstance, compare its ID to the wrapper's ID
        return $event->cart instanceof \AndreiLungeanu\SimpleCart\CartInstance
            && $event->cart->getId() === $cartWrapper->getId();
    });
});

test('can add an item to the cart', function () {
    Event::fake();

    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1)); // Chain on wrapper

    $loadedCart = $cartWrapper->getInstance(); // Get CartInstance from wrapper

    expect($loadedCart->getItems())->toHaveCount(1)
        ->and($loadedCart->getItems()->first()->id)->toBe('item-1')
        ->and($loadedCart->getItems()->first()->quantity)->toBe(1);

    // Should dispatch Created then Updated
    Event::assertDispatchedTimes(CartCreated::class, 1);
    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can add multiple items', function () {
    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 99.99, quantity: 2)); // Chain on wrapper

    $loadedCart = $cartWrapper->getInstance(); // Get CartInstance from wrapper

    $items = $loadedCart->getItems()->keyBy('id');
    expect($items)->toHaveCount(2)
        ->and($items['item-1']->quantity)->toBe(1)
        ->and($items['item-2']->quantity)->toBe(2);
});


test('can update item quantity', function () {
    Event::fake();

    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->updateQuantity('item-1', 3); // Chain on wrapper

    $loadedCart = $cartWrapper->getInstance(); // Get CartInstance from wrapper

    expect($loadedCart->getItems())->toHaveCount(1)
        ->and($loadedCart->getItems()->first()->id)->toBe('item-1')
        ->and($loadedCart->getItems()->first()->quantity)->toBe(3);

    // Created, Updated (add), Updated (update)
    Event::assertDispatchedTimes(CartUpdated::class, 2);
});

test('throws exception when updating quantity for non-existent item', function () {
    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1));

    // Try updating via wrapper
    expect(fn() => $cartWrapper->updateQuantity('non-existent-item', 2))
        ->toThrow(CartException::class); // Exception message might change slightly as it comes from manager now
});

test('throws exception when updating quantity to zero or less', function () {
    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1));

    // Try updating via wrapper
    expect(fn() => $cartWrapper->updateQuantity('item-1', 0))
        ->toThrow(InvalidArgumentException::class, 'Quantity must be positive');

    expect(fn() => $cartWrapper->updateQuantity('item-1', -1))
        ->toThrow(InvalidArgumentException::class, 'Quantity must be positive');
});


test('can get item count', function () {
    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 2))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 99.99, quantity: 3)); // Chain on wrapper

    // Get count via manager Facade
    expect(Cart::itemCount($cartWrapper->getId()))->toBe(5);
});

test('can clear the cart', function () {
    Event::fake();

    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 99.99, quantity: 1)); // Chain on wrapper

    $loadedCart = $cartWrapper->getInstance();
    expect($loadedCart->getItems())->toHaveCount(2);

    $cartWrapper->clear(); // Clear via wrapper

    $clearedCart = $cartWrapper->getInstance(); // Get instance again
    expect($clearedCart->getItems())->toBeEmpty()
        ->and(Cart::itemCount($cartWrapper->getId()))->toBe(0); // Get count via manager

    // Created, Updated(x2), Cleared
    Event::assertDispatched(CartCleared::class, function ($event) use ($cartWrapper) {
        return $event->cartId === $cartWrapper->getId();
    });
});

// Add test for removeItem once implemented
test('can remove an item from the cart', function () {
    Event::fake();

    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-1', name: 'Test Product item-1', price: 99.99, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-2', name: 'Test Product item-2', price: 50.00, quantity: 2)); // Chain on wrapper

    $cartId = $cartWrapper->getId();
    expect(Cart::itemCount($cartId))->toBe(3); // 1 + 2 = 3 // Use manager

    $cartWrapper->removeItem('item-1'); // Remove via wrapper

    $loadedCart = $cartWrapper->getInstance(); // Get instance
    expect($loadedCart->getItems())->toHaveCount(1) // Only item-2 should remain
        ->and($loadedCart->getItems()->first()->id)->toBe('item-2')
        ->and(Cart::itemCount($cartId))->toBe(2); // Quantity of item-2 // Use manager

    // Create(1) + Add(1) + Add(1) + Remove(1) = 3 CartUpdated events
    Event::assertDispatchedTimes(CartUpdated::class, 3);
});

test('can add a note', function () {
    Event::fake();

    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->addNote('This is a test note.'); // Chain on wrapper

    $loadedCart = $cartWrapper->getInstance(); // Get CartInstance from wrapper

    expect($loadedCart->getNotes())->toBeCollection()
        ->and($loadedCart->getNotes())->toHaveCount(1)
        ->and($loadedCart->getNotes()->first())->toBe('This is a test note.');

    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can apply a discount code', function () {
    Event::fake();
    $discount = new DiscountDTO(code: 'TESTCODE', type: 'fixed', value: 5.0); // Use 'value' instead of 'amount'

    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->applyDiscount($discount); // Chain on wrapper

    $loadedCart = $cartWrapper->getInstance(); // Get CartInstance from wrapper

    expect($loadedCart->getDiscounts())->toBeCollection()
        ->and($loadedCart->getDiscounts())->toHaveCount(1)
        ->and($loadedCart->getDiscounts()->first()->code)->toBe('TESTCODE'); // Assuming DiscountDTO structure

    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can set vat exempt status', function () {
    Event::fake();

    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->setVatExempt(true); // Chain on wrapper
    $loadedCart1 = $cartWrapper->getInstance();
    expect($loadedCart1->isVatExempt())->toBeTrue();

    $cartWrapper->setVatExempt(false); // Chain on wrapper
    $loadedCart2 = $cartWrapper->getInstance();
    expect($loadedCart2->isVatExempt())->toBeFalse();

    Event::assertDispatchedTimes(CartUpdated::class, 2);
});

test('can set shipping method', function () {
    Event::fake();
    $shippingInfo = ['vat_rate' => 0.19, 'vat_included' => false];

    $cartWrapper = Cart::create(); // Returns FluentCart wrapper
    $cartWrapper->setShippingMethod('standard', $shippingInfo); // Chain on wrapper

    $loadedCart = $cartWrapper->getInstance(); // Get CartInstance from wrapper

    expect($loadedCart->getShippingMethod())->toBe('standard');
    // We might also want to check internal state if possible/needed, or rely on calculation tests
    expect($loadedCart->getShippingVatInfo()['rate'])->toBe(0.19);

    Event::assertDispatchedTimes(CartUpdated::class, 1);
});

test('can chain methods fluently via wrapper', function () { // Renamed test slightly
    Event::fake();

    $cartWrapper = Cart::create(); // Returns FluentCart wrapper

    // Chain methods on the wrapper
    $cartWrapper->addItem(new CartItemDTO(id: 'item-A', name: 'Test Product item-A', price: 10.00, quantity: 1))
        ->addItem(new CartItemDTO(id: 'item-B', name: 'Test Product item-B', price: 20.00, quantity: 2))
        ->updateQuantity('item-A', 3) // 3 * 10 = 30
        ->applyDiscount(new DiscountDTO(code: 'SUMMER10', type: 'percentage', value: 10.0)) // Use 'value' instead of 'amount'
        ->addNote('Please deliver quickly')
        ->setShippingMethod('express', ['vat_rate' => 0.1]) // Assuming express costs something
        ->setVatExempt(false);

    $loadedCart = $cartWrapper->getInstance(); // Get CartInstance from wrapper

    $items = $loadedCart->getItems()->keyBy('id');
    expect($items)->toHaveCount(2)
        ->and($items['item-A']->quantity)->toBe(3)
        ->and($loadedCart->getDiscounts())->toHaveCount(1)
        ->and($loadedCart->getNotes())->toHaveCount(1)
        ->and($loadedCart->getShippingMethod())->toBe('express')
        ->and($loadedCart->isVatExempt())->toBeFalse();

    // Check counts of events
    Event::assertDispatched(CartCreated::class);
    // 7 updates: addItem, addItem, updateQuantity, applyDiscount, addNote, setShippingMethod, setVatExempt
    Event::assertDispatchedTimes(CartUpdated::class, 7);
});
