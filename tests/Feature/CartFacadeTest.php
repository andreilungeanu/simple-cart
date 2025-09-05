<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Facades\Cart;
use AndreiLungeanu\SimpleCart\Models\Cart as CartModel;
use AndreiLungeanu\SimpleCart\Services\CartService;

describe('Cart Facade', function () {

    it('can create cart via facade', function () {
        $cart = Cart::create(userId: 1);

        expect($cart)->toBeInstanceOf(CartModel::class)
            ->and($cart->user_id)->toBe(1);
    });

    it('can add item via facade', function () {
        $cart = Cart::create(userId: 1);

        $item = Cart::addItem($cart, [
            'product_id' => 'PROD-1',
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 1,
        ]);

        expect($item->product_id)->toBe('PROD-1');
    });

    it('can calculate totals via facade', function () {
        $cart = createTestCartWithItems();
        $cart->update(['shipping_data' => ['method_name' => 'Standard', 'cost' => 5.99], 'tax_zone' => 'US']);
        $cart->refresh();

        $subtotal = Cart::calculateSubtotal($cart);
        $shipping = Cart::calculateShipping($cart);
        $tax = Cart::calculateTax($cart);
        $total = Cart::calculateTotal($cart);

        expect($subtotal)->toBe(74.98)
            ->and($shipping)->toBe(5.99)
            ->and($tax)->toBe(5.10)
            ->and($total)->toBe(86.07);
    });

    it('facade resolves to CartService', function () {
        $service = Cart::getFacadeRoot();

        expect($service)->toBeInstanceOf(CartService::class);
    });

});
