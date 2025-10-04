<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Services\CartService;

describe('CartService - Login Strategy', function () {

    it('keeps user cart when strategy=user', function () {
    config()->set('simple-cart.login_cart_strategy', 'user');

        // Rebind configuration and service to pick up new config
        app()->forgetInstance(\AndreiLungeanu\SimpleCart\Data\CartConfiguration::class);
        app()->instance(
            \AndreiLungeanu\SimpleCart\Data\CartConfiguration::class,
            \AndreiLungeanu\SimpleCart\Data\CartConfiguration::fromConfig(config('simple-cart', []))
        );
        app()->forgetInstance(CartService::class);
        $service = app(CartService::class);

        $userCart = $service->create(userId: 101);
        $service->addItem($userCart, [
            'product_id' => 'U-1',
            'name' => 'User Item',
            'price' => 10.00,
            'quantity' => 1,
        ]);

        $guestCart = $service->create(userId: null, sessionId: 'sess_keep_user');
        $service->addItem($guestCart, [
            'product_id' => 'G-1',
            'name' => 'Guest Item',
            'price' => 5.00,
            'quantity' => 2,
        ]);

        $final = $service->mergeOnLogin(101, 'sess_keep_user');

        expect($final->id)->toBe($userCart->id);
        expect(Cart::find($guestCart->id))->toBeNull();
        expect($final->items->pluck('product_id')->all())->toBe(['U-1']);
    });

    it('keeps guest cart when strategy=guest', function () {
    config()->set('simple-cart.login_cart_strategy', 'guest');

        // Rebind configuration and service to pick up new config
        app()->forgetInstance(\AndreiLungeanu\SimpleCart\Data\CartConfiguration::class);
        app()->instance(
            \AndreiLungeanu\SimpleCart\Data\CartConfiguration::class,
            \AndreiLungeanu\SimpleCart\Data\CartConfiguration::fromConfig(config('simple-cart', []))
        );
        app()->forgetInstance(CartService::class);
        $service = app(CartService::class);

        $userCart = $service->create(userId: 102);
        $service->addItem($userCart, [
            'product_id' => 'U-1',
            'name' => 'User Item',
            'price' => 10.00,
            'quantity' => 1,
        ]);

        $guestCart = $service->create(userId: null, sessionId: 'sess_keep_guest');
        $service->addItem($guestCart, [
            'product_id' => 'G-1',
            'name' => 'Guest Item',
            'price' => 5.00,
            'quantity' => 2,
        ]);

        $final = $service->mergeOnLogin(102, 'sess_keep_guest');

        expect($final->id)->toBe($guestCart->id);
        expect($final->user_id)->toBe(102);
        expect(Cart::find($userCart->id))->toBeNull();
        expect($final->items->pluck('product_id')->all())->toBe(['G-1']);
    });
});
