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
        $cart = createTestCart();

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
