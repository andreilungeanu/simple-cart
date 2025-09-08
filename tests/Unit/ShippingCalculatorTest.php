<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use AndreiLungeanu\SimpleCart\Services\Calculators\ShippingCalculator;

describe('ShippingCalculator - Legacy Tests (Updated for Dynamic Shipping)', function () {

    it('returns zero when no shipping data is set', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_data = null;

        $cost = $calculator->calculate($cart);

        expect($cost)->toBe(0.0);
    });

    it('calculates flat shipping rate from dynamic data', function () {
        $config = CartConfiguration::fromConfig(config('simple-cart'));
        $calculator = new ShippingCalculator($config);

        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Standard Shipping',
            'cost' => 5.99,
        ];
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
        $cart->shipping_data = [
            'method_name' => 'Standard Shipping',
            'cost' => 5.99,
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1]), // Meets free shipping threshold
        ]));

        $cost = $calculator->calculate($cart);

        expect($cost)->toBe(0.0);
    });

    it('does not apply free shipping when threshold is disabled', function () {
        // Create config with disabled free shipping threshold
        $config = CartConfiguration::fromConfig([
            'shipping' => [
                'free_shipping_threshold' => null, // Disabled
            ],
        ]);
        $calculator = new ShippingCalculator($config);

        $cart = Cart::factory()
            ->withShipping(['method_name' => 'Standard Shipping', 'cost' => 5.99])
            ->hasItems(1, [
                'product_id' => 'HIGH-VALUE',
                'name' => 'Expensive Item',
                'price' => 200.00,
                'quantity' => 1,
            ])
            ->create();

        $cost = $calculator->calculate($cart);
        $isFreeShippingApplied = $calculator->isFreeShippingApplied($cart);

        expect($cost)->toBe(5.99) // Should charge shipping despite high cart value
            ->and($isFreeShippingApplied)->toBeFalse(); // Should not detect free shipping as applied
    });

    it('treats zero threshold as disabled', function () {
        // Create config with zero free shipping threshold (should be treated as disabled)
        $config = CartConfiguration::fromConfig([
            'shipping' => [
                'free_shipping_threshold' => 0, // Zero means disabled
            ],
        ]);
        $calculator = new ShippingCalculator($config);

        $cart = Cart::factory()
            ->withShipping(['method_name' => 'Standard Shipping', 'cost' => 5.99])
            ->hasItems(1, [
                'product_id' => 'MEDIUM-VALUE',
                'name' => 'Medium Item',
                'price' => 100.00,
                'quantity' => 1,
            ])
            ->create();

        $cost = $calculator->calculate($cart);

        expect($cost)->toBe(5.99); // Should charge shipping even with cart value > 0
    });

});
