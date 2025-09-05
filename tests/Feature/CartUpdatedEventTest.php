<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Services\CartService;
use Illuminate\Support\Facades\Event;

describe('CartUpdated Event', function () {

    it('is dispatched when cart is created', function () {
        Event::fake();
        $cartService = app(CartService::class);

        $cart = $cartService->create(userId: 1);

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart) {
            return $event->cart->id === $cart->id
                && $event->action === 'created'
                && is_array($event->metadata);
        });
    });

    it('is dispatched when item is added', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $item = $cartService->addItem($cart, [
            'product_id' => 'PROD-1',
            'name' => 'Test Product',
            'price' => 29.99,
        ]);

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart, $item) {
            return $event->cart->id === $cart->id
                && $event->action === 'item_added'
                && $event->metadata['item']->id === $item->id;
        });
    });

    it('is dispatched when item is updated', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = createTestCartWithItems();

        $cartService->updateQuantity($cart, 'PROD-1', 5);

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart) {
            return $event->cart->id === $cart->id
                && $event->action === 'item_updated'
                && $event->metadata['product_id'] === 'PROD-1';
        });
    });

    it('is dispatched when item is removed', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = createTestCartWithItems();

        $cartService->removeItem($cart, 'PROD-1');

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart) {
            return $event->cart->id === $cart->id
                && $event->action === 'item_removed'
                && $event->metadata['product_id'] === 'PROD-1';
        });
    });

    it('is dispatched when discount is applied', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $discountData = [
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20,
            'conditions' => [],
        ];

        $cartService->applyDiscount($cart, $discountData);

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart) {
            return $event->cart->id === $cart->id
                && $event->action === 'discount_applied'
                && $event->metadata['code'] === 'SAVE20';
        });
    });

    it('is dispatched when discount is removed', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $discountData = [
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20,
            'conditions' => [],
        ];

        $cartService->applyDiscount($cart, $discountData);
        $cartService->removeDiscount($cart, 'SAVE20');

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart) {
            return $event->cart->id === $cart->id
                && $event->action === 'discount_removed'
                && $event->metadata['code'] === 'SAVE20';
        });
    });

    it('is dispatched when shipping is applied', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $cartService->applyShipping($cart, [
            'method_name' => 'Express Shipping',
            'cost' => 15.99,
        ]);

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart) {
            return $event->cart->id === $cart->id
                && $event->action === 'shipping_applied'
                && $event->metadata['method'] === 'Express Shipping';
        });
    });

    it('is dispatched when tax zone is set', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $cartService->setTaxZone($cart, 'RO');

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart) {
            return $event->cart->id === $cart->id
                && $event->action === 'tax_zone_updated'
                && $event->metadata['zone'] === 'RO';
        });
    });

    it('is dispatched when cart is cleared', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = createTestCartWithItems();

        $cartService->clear($cart);

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart) {
            return $event->cart->id === $cart->id
                && $event->action === 'cleared';
        });
    });

    it('is dispatched when cart is deleted', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);
        $cartId = $cart->id;

        $cartService->delete($cart);

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cartId) {
            return $event->action === 'deleted'
                && $event->metadata['cart_id'] === $cartId;
        });
    });

    it('has correct event structure', function () {
        $cart = new Cart(['id' => 'test-cart-id']);
        $event = new CartUpdated($cart, 'test_action', ['key' => 'value']);

        expect($event->cart)->toBe($cart)
            ->and($event->action)->toBe('test_action')
            ->and($event->metadata)->toBe(['key' => 'value']);
    });

    it('has default metadata as empty array', function () {
        $cart = new Cart(['id' => 'test-cart-id']);
        $event = new CartUpdated($cart, 'test_action');

        expect($event->metadata)->toBe([]);
    });

});
