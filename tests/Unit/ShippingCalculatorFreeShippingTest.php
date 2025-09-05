<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use AndreiLungeanu\SimpleCart\Services\Calculators\ShippingCalculator;

describe('ShippingCalculator - Free Shipping Integration', function () {

    beforeEach(function () {
        $this->config = CartConfiguration::fromConfig(config('simple-cart'));
        $this->calculator = new ShippingCalculator($this->config);
    });

    it('applies free shipping for discount-based free shipping', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Standard Shipping',
            'cost' => 12.99,
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 50.0, 'quantity' => 1]), // Below threshold
        ]));

        $appliedDiscounts = [
            'FREESHIP' => [
                'code' => 'FREESHIP',
                'type' => 'free_shipping',
                'value' => 0.0,
                'conditions' => [],
            ],
        ];

        $cost = $this->calculator->calculate($cart, $appliedDiscounts);
        $isFreeShipping = $this->calculator->isFreeShippingApplied($cart, $appliedDiscounts);

        expect($cost)->toBe(0.0)
            ->and($isFreeShipping)->toBeTrue();
    });

    it('prioritizes discount-based free shipping over threshold-based', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Express Shipping',
            'cost' => 25.99,
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 150.0, 'quantity' => 1]), // Above threshold
        ]));

        $appliedDiscounts = [
            'FREESHIP' => [
                'code' => 'FREESHIP',
                'type' => 'free_shipping',
                'value' => 0.0,
                'conditions' => [],
            ],
        ];

        $cost = $this->calculator->calculate($cart, $appliedDiscounts);
        $isFreeShipping = $this->calculator->isFreeShippingApplied($cart, $appliedDiscounts);

        expect($cost)->toBe(0.0) // Free because of discount, not threshold
            ->and($isFreeShipping)->toBeTrue();
    });

    it('falls back to threshold-based free shipping when no discount provided', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Standard Shipping',
            'cost' => 8.99,
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1]), // Meets threshold
        ]));

        $cost = $this->calculator->calculate($cart, null); // No discounts provided
        $isFreeShipping = $this->calculator->isFreeShippingApplied($cart, null);

        expect($cost)->toBe(0.0) // Free because of threshold
            ->and($isFreeShipping)->toBeTrue();
    });

    it('charges shipping when neither discount nor threshold applies', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Standard Shipping',
            'cost' => 6.99,
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 75.0, 'quantity' => 1]), // Below threshold
        ]));

        $appliedDiscounts = [
            'SAVE10' => [
                'code' => 'SAVE10',
                'type' => 'fixed',
                'value' => 10.0,
                'conditions' => [],
            ],
        ];

        $cost = $this->calculator->calculate($cart, $appliedDiscounts);
        $isFreeShipping = $this->calculator->isFreeShippingApplied($cart, $appliedDiscounts);

        expect($cost)->toBe(6.99) // Should charge shipping
            ->and($isFreeShipping)->toBeFalse();
    });

    it('checks cart discount_data directly when appliedDiscounts is null', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Standard Shipping',
            'cost' => 9.99,
        ];
        $cart->discount_data = [
            'FREESHIP' => [
                'code' => 'FREESHIP',
                'type' => 'free_shipping',
                'value' => 0.0,
                'conditions' => [],
            ],
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 50.0, 'quantity' => 1]), // Below threshold
        ]));

        $cost = $this->calculator->calculate($cart); // Uses cart's discount_data
        $isFreeShipping = $this->calculator->isFreeShippingApplied($cart);

        expect($cost)->toBe(0.0)
            ->and($isFreeShipping)->toBeTrue();
    });

});
