<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use AndreiLungeanu\SimpleCart\Services\Calculators\ShippingCalculator;

describe('ShippingCalculator', function () {

    it('returns zero when no shipping method is set', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_method = null;

        $cost = $calculator->calculate($cart);

        expect($cost)->toBe(0.0);
    });

    it('returns zero for unknown shipping method', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_method = 'unknown';
        $cart->setRelation('items', collect([
            new CartItem(['price' => 50.0, 'quantity' => 1]),
        ]));

        $cost = $calculator->calculate($cart);

        expect($cost)->toBe(0.0);
    });

    it('calculates flat shipping rate', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_method = 'standard';
        $cart->setRelation('items', collect([
            new CartItem(['price' => 50.0, 'quantity' => 1]),
        ]));

        $cost = $calculator->calculate($cart);

        expect($cost)->toBe(5.99);
    });

    it('applies free shipping when threshold is met', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_method = 'standard';
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1]), // Meets free shipping threshold
        ]));

        $cost = $calculator->calculate($cart);

        expect($cost)->toBe(0.0);
    });

    it('correctly identifies when free shipping is applied', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_method = 'standard';
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1]),
        ]));

        $isFreeShipping = $calculator->isFreeShippingApplied($cart);

        expect($isFreeShipping)->toBeTrue();
    });

    it('returns false for free shipping when below threshold', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_method = 'standard';
        $cart->setRelation('items', collect([
            new CartItem(['price' => 50.0, 'quantity' => 1]),
        ]));

        $isFreeShipping = $calculator->isFreeShippingApplied($cart);

        expect($isFreeShipping)->toBeFalse();
    });

    it('returns false for free shipping when no method set', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_method = null;
        $cart->setRelation('items', collect([
            new CartItem(['price' => 150.0, 'quantity' => 1]),
        ]));

        $isFreeShipping = $calculator->isFreeShippingApplied($cart);

        expect($isFreeShipping)->toBeFalse();
    });

    it('gets available shipping methods', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $methods = $calculator->getAvailableMethods($cart);

        expect($methods)->toHaveKey('standard')
            ->and($methods)->toHaveKey('express')
            ->and($methods['standard']['name'])->toBe('Standard Shipping')
            ->and($methods['standard']['cost'])->toBe(5.99)
            ->and($methods['express']['name'])->toBe('Express Shipping')
            ->and($methods['express']['cost'])->toBe(15.99);
    });

    it('calculates weight-based shipping', function () {
        $config = CartConfiguration::fromConfig(array_merge(config('simple-cart'), [
            'shipping' => [
                'settings' => [
                    'free_shipping_threshold' => 1000.0, // High threshold to avoid free shipping
                    'methods' => [
                        'weight_based' => [
                            'name' => 'Weight Based Shipping',
                            'type' => 'weight',
                            'rate_per_kg' => 2.50,
                        ],
                    ],
                ],
            ],
        ]));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_method = 'weight_based';
        $cart->setRelation('items', collect([
            new CartItem([
                'price' => 50.0,
                'quantity' => 2,
                'metadata' => ['weight' => 1.5],
            ]), // Total weight: 3kg
        ]));

        $cost = $calculator->calculate($cart);

        expect($cost)->toBe(7.5); // 3kg * 2.50 per kg
    });

    it('calculates percentage-based shipping', function () {
        $config = CartConfiguration::fromConfig(array_merge(config('simple-cart'), [
            'shipping' => [
                'settings' => [
                    'free_shipping_threshold' => 1000.0, // High threshold
                    'methods' => [
                        'percentage' => [
                            'name' => 'Percentage Shipping',
                            'type' => 'percentage',
                            'rate' => 0.05, // 5%
                        ],
                    ],
                ],
            ],
        ]));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_method = 'percentage';
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1]),
        ]));

        $cost = $calculator->calculate($cart);

        expect($cost)->toBe(5.0); // 100 * 0.05
    });

});
