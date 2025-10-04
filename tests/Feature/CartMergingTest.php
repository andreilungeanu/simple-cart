<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Services\CartService;
use Illuminate\Support\Facades\Event;

describe('CartService - Merging on Login', function () {
    beforeEach(function () {
        $this->service = app(CartService::class);
    });

    it('claims guest cart when no user cart exists', function () {
        Event::fake();

        $sessionId = 'sess_guest_1';
        $guestCart = $this->service->create(userId: null, sessionId: $sessionId);
        $this->service->addItem($guestCart, [
            'product_id' => 'G-1',
            'name' => 'Guest Item',
            'price' => 10.00,
            'quantity' => 2,
        ]);

        $merged = $this->service->mergeOnLogin(userId: 10, sessionId: $sessionId);

        expect($merged)->not()->toBeNull()
            ->and($merged->user_id)->toBe(10)
            ->and($merged->items)->toHaveCount(1)
            ->and($merged->items->first()->product_id)->toBe('G-1');

        Event::assertDispatched(CartUpdated::class, function ($e) use ($merged) {
            return $e->action === 'merged' && $e->cart->id === $merged->id;
        });
    });

    it('no-op when only user cart exists', function () {
        $userCart = $this->service->create(userId: 20);
        $this->service->addItem($userCart, [
            'product_id' => 'U-1',
            'name' => 'User Item',
            'price' => 25.00,
            'quantity' => 1,
        ]);

        $merged = $this->service->mergeOnLogin(userId: 20, sessionId: 'unknown_sess');

        expect($merged)->toBeInstanceOf(Cart::class)
            ->and($merged->id)->toBe($userCart->id)
            ->and($merged->items)->toHaveCount(1)
            ->and($merged->items->first()->product_id)->toBe('U-1');
    });

    it('merges guest cart into user cart', function () {
        Event::fake();

        // Existing user cart
        $userCart = $this->service->create(userId: 30);
        $this->service->addItem($userCart, [
            'product_id' => 'SKU-1',
            'name' => 'Existing',
            'price' => 100.00,
            'quantity' => 1,
        ]);
        $this->service->applyDiscount($userCart, [
            'code' => 'USER10',
            'type' => 'percentage',
            'value' => 10,
        ]);
        $this->service->applyShipping($userCart, ['method_name' => 'Standard', 'cost' => 4.99]);

        // Guest cart on session
        $sessionId = 'sess_guest_2';
        $guestCart = $this->service->create(userId: null, sessionId: $sessionId);
        $this->service->addItem($guestCart, [
            'product_id' => 'SKU-1', // same product -> quantity sums
            'name' => 'Existing',
            'price' => 100.00,
            'quantity' => 2,
        ]);
        $this->service->addItem($guestCart, [
            'product_id' => 'SKU-2',
            'name' => 'New Guest Item',
            'price' => 50.00,
            'quantity' => 1,
        ]);
        $this->service->applyDiscount($guestCart, [
            'code' => 'GUEST5',
            'type' => 'fixed',
            'value' => 5.0,
        ]);

        $merged = $this->service->mergeOnLogin(userId: 30, sessionId: $sessionId);

        // User cart receives merged items
        $merged->refresh(['items']);
        $sku1 = $merged->items->firstWhere('product_id', 'SKU-1');
        $sku2 = $merged->items->firstWhere('product_id', 'SKU-2');

        expect($merged->id)->toBe($userCart->id)
            ->and($sku1->quantity)->toBe(3) // 1 + 2
            ->and($sku2)->not()->toBeNull()
            ->and($merged->discount_data)->toHaveKey('USER10')
            ->and($merged->discount_data)->toHaveKey('GUEST5')
            ->and($merged->shipping_data['method_name'])->toBe('Standard'); // kept from user

        // Ensure guest cart is deleted
        expect(Cart::find($guestCart->id))->toBeNull();

        Event::assertDispatched(CartUpdated::class, function ($e) use ($userCart) {
            return $e->action === 'merged' && $e->cart->id === $userCart->id;
        });
    });
});
