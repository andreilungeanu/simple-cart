<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use AndreiLungeanu\SimpleCart\Services\Calculators\ShippingCalculator;

describe('ShippingCalculator - Dynamic Shipping', function () {

    beforeEach(function () {
        $this->config = CartConfiguration::fromConfig(config('simple-cart'));
        $this->calculator = new ShippingCalculator($this->config);
    });

    it('returns zero when no shipping data is set', function () {
        $cart = new Cart();
        $cart->shipping_data = null;

        $cost = $this->calculator->calculate($cart);

        expect($cost)->toBe(0.0);
    });

    it('returns zero when shipping data has no cost', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Free Shipping',
        ];

        $cost = $this->calculator->calculate($cart);

        expect($cost)->toBe(0.0);
    });

    it('calculates shipping cost from stored data', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'UPS Ground',
            'cost' => 12.99,
            'carrier' => 'UPS',
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 50.0, 'quantity' => 1]),
        ]));

        $cost = $this->calculator->calculate($cart);

        expect($cost)->toBe(12.99);
    });

    it('applies free shipping when threshold is met', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Standard Shipping',
            'cost' => 9.99,
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1]), // Meets free shipping threshold
        ]));

        $cost = $this->calculator->calculate($cart);

        expect($cost)->toBe(0.0);
    });

    it('correctly identifies when free shipping is applied', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Express',
            'cost' => 15.99,
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1]),
        ]));

        $isFreeShipping = $this->calculator->isFreeShippingApplied($cart);

        expect($isFreeShipping)->toBeTrue();
    });

    it('returns false for free shipping when below threshold', function () {
        $cart = new Cart();
        $cart->shipping_data = [
            'method_name' => 'Standard',
            'cost' => 5.99,
        ];
        $cart->setRelation('items', collect([
            new CartItem(['price' => 50.0, 'quantity' => 1]),
        ]));

        $isFreeShipping = $this->calculator->isFreeShippingApplied($cart);

        expect($isFreeShipping)->toBeFalse();
    });

    it('returns false for free shipping when no shipping data', function () {
        $cart = new Cart();
        $cart->shipping_data = null;
        $cart->setRelation('items', collect([
            new CartItem(['price' => 150.0, 'quantity' => 1]),
        ]));

        $isFreeShipping = $this->calculator->isFreeShippingApplied($cart);

        expect($isFreeShipping)->toBeFalse();
    });

    it('gets applied shipping data', function () {
        $cart = new Cart();
        $shippingData = [
            'method_name' => 'FedEx Overnight',
            'cost' => 29.99,
            'carrier' => 'FedEx',
            'estimated_delivery' => '1 business day',
        ];
        $cart->shipping_data = $shippingData;

        $appliedShipping = $this->calculator->getAppliedShipping($cart);

        expect($appliedShipping)->toBe($shippingData);
    });

    it('returns null when no shipping is applied', function () {
        $cart = new Cart();
        $cart->shipping_data = null;

        $appliedShipping = $this->calculator->getAppliedShipping($cart);

        expect($appliedShipping)->toBeNull();
    });

    it('handles different shipping cost types correctly', function () {
        $cart = new Cart();
        $cart->setRelation('items', collect([
            new CartItem(['price' => 25.0, 'quantity' => 2]), // $50 total, below threshold
        ]));

        // Test string cost (should be converted to float)
        $cart->shipping_data = ['method_name' => 'Test', 'cost' => '7.50'];
        expect($this->calculator->calculate($cart))->toBe(7.5);

        // Test integer cost
        $cart->shipping_data = ['method_name' => 'Test', 'cost' => 10];
        expect($this->calculator->calculate($cart))->toBe(10.0);

        // Test float cost
        $cart->shipping_data = ['method_name' => 'Test', 'cost' => 12.75];
        expect($this->calculator->calculate($cart))->toBe(12.75);
    });
});
