<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use AndreiLungeanu\SimpleCart\Services\Calculators\TaxCalculator;

describe('TaxCalculator', function () {
    beforeEach(function () {
        $this->config = CartConfiguration::fromConfig(config('simple-cart'));
        $this->calculator = new TaxCalculator($this->config);
    });

    it('returns zero tax when no tax zone is set', function () {
        $cart = new Cart(['tax_zone' => null]);

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(0.0);
    });

    it('returns zero tax for unknown tax zone', function () {
        $cart = new Cart(['tax_zone' => 'UNKNOWN']);

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(0.0);
    });

    it('calculates tax correctly for various zones and amounts', function (string $zone, float $subtotal, float $expectedTax) {
        $cart = new Cart(['tax_zone' => $zone]);
        $cart->setRelation('items', collect([
            new CartItem(['price' => $subtotal, 'quantity' => 1, 'category' => null]),
        ]));

        $tax = $this->calculator->calculate($cart, $subtotal);

        expect($tax)->toBe($expectedTax);
    })->with([
        'US basic' => ['US', 100.0, 7.25],
        'US larger amount' => ['US', 250.0, 18.13],
        'RO basic' => ['RO', 100.0, 19.0],
        'RO larger amount' => ['RO', 150.0, 28.5],
    ]);

    it('applies category-specific tax rates', function () {
        $cart = new Cart(['tax_zone' => 'RO']);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'category' => 'books']),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(5.0); // Books have 5% tax in RO
    });

    it('calculates mixed category taxes correctly', function () {
        $cart = new Cart(['tax_zone' => 'RO']);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'category' => 'books']), // 5%
            new CartItem(['price' => 50.0, 'quantity' => 1, 'category' => 'food']),    // 9%
        ]));

        $tax = $this->calculator->calculate($cart, 150.0);

        // Books: $100 * 0.05 = $5.00
        // Food: $50 * 0.09 = $4.50
        // Total: $9.50
        expect($tax)->toBe(9.5);
    });

    it('gets effective rate for category', function () {
        $cartUS = new Cart(['tax_zone' => 'US']);
        $cartRO = new Cart(['tax_zone' => 'RO']);

        expect($this->calculator->getEffectiveRate($cartRO, 'books'))->toBe(0.05)
            ->and($this->calculator->getEffectiveRate($cartRO, 'food'))->toBe(0.09)
            ->and($this->calculator->getEffectiveRate($cartUS, 'digital'))->toBe(0.0)
            ->and($this->calculator->getEffectiveRate($cartUS, null))->toBe(0.0725);
    });

    it('includes shipping in tax when configured', function () {
        $cart = new Cart(['tax_zone' => 'RO']);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'category' => null]),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0, 10.0, true);

        expect($tax)->toBe(20.9); // (100 + 10) * 0.19
    })->skipOnCi('Shipping tax configuration varies by environment');
});
