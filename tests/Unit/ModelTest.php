<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Enums\CartStatusEnum;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;

describe('Cart Model', function () {

    it('has correct fillable attributes', function () {
        $cart = new Cart();

        $fillable = $cart->getFillable();

        expect($fillable)->toContain('user_id')
            ->and($fillable)->toContain('session_id')
            ->and($fillable)->toContain('shipping_data')
            ->and($fillable)->toContain('tax_data')
            ->and($fillable)->toContain('discount_data')
            ->and($fillable)->toContain('metadata')
            ->and($fillable)->toContain('status')
            ->and($fillable)->toContain('expires_at');
    });

    it('casts attributes correctly', function () {
        $cart = new Cart([
            'discount_data' => ['SAVE10' => ['code' => 'SAVE10', 'type' => 'fixed', 'value' => 10]],
            'metadata' => ['key' => 'value'],
            'status' => 'active',
        ]);

        expect($cart->discount_data)->toBeArray()
            ->and($cart->metadata)->toBeArray()
            ->and($cart->status)->toBeInstanceOf(CartStatusEnum::class);
    });

    it('has items relationship', function () {
        $cart = Cart::factory()->create();

        $relation = $cart->items();

        expect($relation)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('checks if cart is expired', function () {
        $expiredCart = new Cart(['expires_at' => now()->subDay()]);
        $activeCart = new Cart(['expires_at' => now()->addDay()]);
        $noExpiryCart = new Cart(['expires_at' => null]);

        expect($expiredCart->isExpired())->toBeTrue()
            ->and($activeCart->isExpired())->toBeFalse()
            ->and($noExpiryCart->isExpired())->toBeFalse();
    });

    it('calculates subtotal from items', function () {
        $cart = new Cart();
        $cart->setRelation('items', collect([
            new CartItem(['price' => 29.99, 'quantity' => 2]),
            new CartItem(['price' => 15.00, 'quantity' => 1]),
        ]));

        $subtotal = $cart->subtotal;

        expect($subtotal)->toBe(74.98);
    });

    it('calculates item count', function () {
        $cart = new Cart();
        $cart->setRelation('items', collect([
            new CartItem(['quantity' => 2]),
            new CartItem(['quantity' => 1]),
            new CartItem(['quantity' => 3]),
        ]));

        $itemCount = $cart->item_count;

        expect($itemCount)->toBe(6);
    });

});

describe('CartItem Model', function () {

    it('has correct fillable attributes', function () {
        $item = new CartItem();

        $fillable = $item->getFillable();

        expect($fillable)->toContain('cart_id')
            ->and($fillable)->toContain('product_id')
            ->and($fillable)->toContain('name')
            ->and($fillable)->toContain('price')
            ->and($fillable)->toContain('quantity')
            ->and($fillable)->toContain('category')
            ->and($fillable)->toContain('metadata');
    });

    it('casts attributes correctly', function () {
        $item = new CartItem([
            'price' => '29.99',
            'quantity' => '2',
            'metadata' => ['color' => 'blue'],
        ]);

        expect($item->price)->toBe('29.99')
            ->and($item->quantity)->toBe(2)
            ->and($item->metadata)->toBeArray();
    });

    it('has cart relationship', function () {
        $item = new CartItem();

        $relation = $item->cart();

        expect($relation)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('calculates line total correctly', function () {
        $item = new CartItem(['price' => 29.99, 'quantity' => 2]);

        $lineTotal = $item->getLineTotal();

        expect($lineTotal)->toBe(59.98);
    });

});

describe('Cart Model Scopes', function () {

    beforeEach(function () {
        // Create test carts with different statuses and dates
        Cart::create([
            'user_id' => 1,
            'session_id' => 'session1',
            'status' => CartStatusEnum::Active,
            'expires_at' => now()->addDays(7),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(1),
        ]);

        // Create an old expired cart with explicit timestamp
        $oldCart = Cart::create([
            'user_id' => 2,
            'session_id' => 'session2',
            'status' => CartStatusEnum::Expired,
            'expires_at' => now()->subDays(1),
            'created_at' => now()->subDays(5),
        ]);
        // Force update the updated_at timestamp
        $oldCart->updated_at = now()->subDays(5);
        $oldCart->save();

        Cart::create([
            'user_id' => 1,
            'session_id' => 'session3',
            'status' => CartStatusEnum::Abandoned,
            'expires_at' => now()->addDays(1),
            'created_at' => now()->subDays(1),
            'updated_at' => now(),
        ]);
    });

    it('filters active carts', function () {
        $activeCarts = Cart::active()->get();

        expect($activeCarts)->toHaveCount(1)
            ->and($activeCarts->first()->status)->toBe(CartStatusEnum::Active);
    });

    it('filters expired carts', function () {
        $expiredCarts = Cart::expired()->get();

        expect($expiredCarts)->toHaveCount(1)
            ->and($expiredCarts->first()->status)->toBe(CartStatusEnum::Expired);
    });

    it('filters abandoned carts', function () {
        $abandonedCarts = Cart::abandoned()->get();

        expect($abandonedCarts)->toHaveCount(1)
            ->and($abandonedCarts->first()->status)->toBe(CartStatusEnum::Abandoned);
    });

    it('filters carts not expired by date', function () {
        $notExpiredCarts = Cart::notExpiredByDate()->get();

        expect($notExpiredCarts)->toHaveCount(2); // Active and Abandoned carts with future expiry dates
    });

    it('filters carts expired before a date', function () {
        $expiredBeforeCarts = Cart::expiredBefore(now())->get();

        expect($expiredBeforeCarts)->toHaveCount(1)
            ->and($expiredBeforeCarts->first()->status)->toBe(CartStatusEnum::Expired);
    });

    it('filters empty carts', function () {
        // All test carts are empty by default
        $emptyCarts = Cart::empty()->get();

        expect($emptyCarts)->toHaveCount(3);
    });

    it('filters carts for specific user', function () {
        $userCarts = Cart::forUser(1)->get();

        expect($userCarts)->toHaveCount(2);

        $userCarts->each(function ($cart) {
            expect($cart->user_id)->toBe(1);
        });
    });

    it('filters carts for specific session', function () {
        $sessionCarts = Cart::forSession('session1')->get();

        expect($sessionCarts)->toHaveCount(1)
            ->and($sessionCarts->first()->session_id)->toBe('session1');
    });

    it('filters carts older than specified days', function () {
        $oldCarts = Cart::olderThan(3)->get();

        expect($oldCarts)->toHaveCount(1)
            ->and($oldCarts->first()->status)->toBe(CartStatusEnum::Expired);
    });

    it('can chain multiple scopes', function () {
        $result = Cart::active()->forUser(1)->get();

        expect($result)->toHaveCount(1)
            ->and($result->first()->status)->toBe(CartStatusEnum::Active)
            ->and($result->first()->user_id)->toBe(1);
    });

    it('filters carts not in expired status', function () {
        // Clear existing carts to ensure clean test state
        Cart::query()->delete();

        // Create carts with different statuses
        Cart::create(['status' => CartStatusEnum::Active, 'expires_at' => now()->addDays(5)]);
        Cart::create(['status' => CartStatusEnum::Abandoned, 'expires_at' => now()->addDays(3)]);
        Cart::create(['status' => CartStatusEnum::Expired, 'expires_at' => now()->subDays(1)]);

        $notExpiredStatus = Cart::notExpired()->get();

        expect($notExpiredStatus)->toHaveCount(2);

        $statuses = $notExpiredStatus->pluck('status')->unique()->values();
        expect($statuses)->toContain(CartStatusEnum::Active)
            ->and($statuses)->toContain(CartStatusEnum::Abandoned)
            ->and($statuses)->not->toContain(CartStatusEnum::Expired);
    });

});
