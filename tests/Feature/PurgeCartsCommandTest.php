<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Console\Commands\PurgeCartsCommand;
use AndreiLungeanu\SimpleCart\Enums\CartStatusEnum;
use AndreiLungeanu\SimpleCart\Models\Cart;
use Illuminate\Support\Facades\Artisan;

describe('PurgeCartsCommand', function () {

    it('marks expired carts as expired', function () {
        // Create expired cart
        $expiredCart = Cart::create([
            'user_id' => 1,
            'status' => CartStatusEnum::Active,
            'expires_at' => now()->subDays(5),
        ]);

        // Create active cart
        $activeCart = Cart::create([
            'user_id' => 2,
            'status' => CartStatusEnum::Active,
            'expires_at' => now()->addDays(5),
        ]);

        Artisan::call('simple-cart:cleanup', ['--force' => true]);

        $expiredCart->refresh();
        $activeCart->refresh();

        expect($expiredCart->status)->toBe(CartStatusEnum::Expired)
            ->and($activeCart->status)->toBe(CartStatusEnum::Active);
    });

    it('deletes old carts', function () {
        // Create old expired cart that's already been marked as expired
        $oldCart = new Cart([
            'user_id' => 1,
            'status' => CartStatusEnum::Expired,
            'expires_at' => now()->subDays(5),
        ]);

        // Manually set timestamps to be 35 days old
        $oldDate = now()->subDays(35);
        $oldCart->created_at = $oldDate;
        $oldCart->updated_at = $oldDate;
        $oldCart->save();

        // Create recent cart
        $recentCart = Cart::create([
            'user_id' => 2,
            'status' => CartStatusEnum::Active,
            'expires_at' => now()->addDays(5),
        ]);

        // First command run should find and delete the old expired cart
        Artisan::call('simple-cart:cleanup', ['--force' => true, '--days' => 30]);

        expect(Cart::find($oldCart->id))->toBeNull()
            ->and(Cart::find($recentCart->id))->not()->toBeNull();
    });

    it('marks empty carts as abandoned', function () {
        // Create empty cart older than 1 day
        $emptyCart = Cart::create([
            'user_id' => 1,
            'status' => CartStatusEnum::Active,
            'expires_at' => now()->addDays(5),
        ]);
        $emptyCart->created_at = now()->subDays(2);
        $emptyCart->updated_at = now()->subDays(2);
        $emptyCart->save();

        // Create cart with items
        $cartWithItems = Cart::factory()->withTestItems()->create();
        $cartWithItems->created_at = now()->subDays(2);
        $cartWithItems->updated_at = now()->subDays(2);
        $cartWithItems->save();

        Artisan::call('simple-cart:cleanup', ['--force' => true]);

        $emptyCart->refresh();
        $cartWithItems->refresh();

        expect($emptyCart->status)->toBe(CartStatusEnum::Abandoned)
            ->and($cartWithItems->status)->toBe(CartStatusEnum::Active);
    });

    it('respects custom days parameter', function () {
        // Create old abandoned cart (already marked)
        $cart = Cart::create([
            'user_id' => 1,
            'status' => CartStatusEnum::Abandoned,
            'expires_at' => now()->addDays(30), // Future date so it doesn't get marked as expired
        ]);

        // Manually set timestamps to be 10 days old
        $oldDate = now()->subDays(10);
        $cart->created_at = $oldDate;
        $cart->updated_at = $oldDate;
        $cart->save();

        // Should not be deleted with --days=15
        Artisan::call('simple-cart:cleanup', ['--force' => true, '--days' => 15]);
        expect(Cart::find($cart->id))->not()->toBeNull();

        // Should be deleted with --days=5
        Artisan::call('simple-cart:cleanup', ['--force' => true, '--days' => 5]);
        expect(Cart::find($cart->id))->toBeNull();
    });

    it('returns success exit code', function () {
        $exitCode = Artisan::call('simple-cart:cleanup', ['--force' => true]);

        expect($exitCode)->toBe(0);
    });

    it('has correct signature and description', function () {
        $command = new PurgeCartsCommand();

        expect($command->getName())->toBe('simple-cart:cleanup')
            ->and($command->getDescription())->toBe('Clean up expired and abandoned carts');
    });

});
