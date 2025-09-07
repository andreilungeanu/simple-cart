<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use AndreiLungeanu\SimpleCart\Services\Calculators\TaxCalculator;

describe('TaxCalculator', function () {
    beforeEach(function () {
        $this->calculator = new TaxCalculator();
    });

    it('returns zero tax when no tax data is set', function () {
        $cart = new Cart(['tax_data' => null]);

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(0.0);
    });

    it('calculates tax correctly with basic rate', function () {
        $cart = new Cart([
            'tax_data' => [
                'code' => 'SALES_TAX',
                'name' => 'Sales Tax',
                'rate' => 0.0725,
            ],
        ]);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'product_id' => 'prod_1']),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(7.25);
    });

    it('applies item-specific tax rates (highest priority)', function () {
        $cart = new Cart([
            'tax_data' => [
                'code' => 'MIXED_TAX',
                'rate' => 0.10,
                'conditions' => [
                    'rates_per_item' => [
                        'prod_1' => 0.15,
                    ],
                    'rates_per_category' => [
                        'books' => 0.05,
                    ],
                ],
            ],
        ]);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'product_id' => 'prod_1', 'category' => 'books']),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(15.0); // Item rate (0.15) takes priority over category rate (0.05)
    });

    it('applies category-specific tax rates over type-specific rates', function () {
        $cart = new Cart([
            'tax_data' => [
                'code' => 'PRIORITY_TEST',
                'rate' => 0.10,
                'conditions' => [
                    'rates_per_category' => [
                        'books' => 0.05,
                    ],
                    'rates_per_type' => [
                        'luxury' => 0.25,
                    ],
                ],
            ],
        ]);
        $cart->setRelation('items', collect([
            new CartItem([
                'price' => 100.0,
                'quantity' => 1,
                'product_id' => 'prod_1',
                'category' => 'books',
                'metadata' => ['type' => 'luxury'],
            ]),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(5.0); // Category rate (0.05) takes priority over type rate (0.25)
    });

    it('applies type-specific tax rates from metadata', function () {
        $cart = new Cart([
            'tax_data' => [
                'code' => 'TYPE_TAX',
                'rate' => 0.10,
                'conditions' => [
                    'rates_per_type' => [
                        'luxury' => 0.25,
                    ],
                ],
            ],
        ]);
        $cart->setRelation('items', collect([
            new CartItem([
                'price' => 100.0,
                'quantity' => 1,
                'product_id' => 'prod_1',
                'metadata' => ['type' => 'luxury'],
            ]),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(25.0); // Type rate (0.25) applied
    });

    it('applies category-specific tax rates', function () {
        $cart = new Cart([
            'tax_data' => [
                'code' => 'VAT',
                'rate' => 0.20,
                'conditions' => [
                    'rates_per_category' => [
                        'books' => 0.05,
                    ],
                ],
            ],
        ]);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'product_id' => 'prod_1', 'category' => 'books']),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(5.0); // Category rate (0.05) applied
    });

    it('falls back to default rate when no specific rate applies', function () {
        $cart = new Cart([
            'tax_data' => [
                'code' => 'DEFAULT_TAX',
                'rate' => 0.20,
                'conditions' => [
                    'rates_per_category' => [
                        'books' => 0.05,
                    ],
                ],
            ],
        ]);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'product_id' => 'prod_1', 'category' => 'electronics']),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0);

        expect($tax)->toBe(20.0); // Default rate (0.20) applied
    });

    it('calculates mixed item taxes correctly', function () {
        $cart = new Cart([
            'tax_data' => [
                'code' => 'MIXED_TAX',
                'rate' => 0.20,
                'conditions' => [
                    'rates_per_item' => [
                        'prod_1' => 0.0, // Tax exempt
                    ],
                    'rates_per_category' => [
                        'books' => 0.05,
                    ],
                ],
            ],
        ]);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'product_id' => 'prod_1', 'category' => 'electronics']), // 0% (item override)
            new CartItem(['price' => 50.0, 'quantity' => 1, 'product_id' => 'prod_2', 'category' => 'books']),        // 5% (category)
            new CartItem(['price' => 75.0, 'quantity' => 1, 'product_id' => 'prod_3', 'category' => 'clothing']),    // 20% (default)
        ]));

        $tax = $this->calculator->calculate($cart, 225.0);

        // prod_1: $100 * 0.0 = $0.00
        // prod_2: $50 * 0.05 = $2.50
        // prod_3: $75 * 0.20 = $15.00
        // Total: $17.50
        expect($tax)->toBe(17.5);
    });

    it('includes shipping in tax when configured', function () {
        $cart = new Cart([
            'tax_data' => [
                'code' => 'VAT_WITH_SHIPPING',
                'rate' => 0.20,
                'apply_to_shipping' => true,
            ],
        ]);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'product_id' => 'prod_1']),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0, 10.0);

        expect($tax)->toBe(22.0); // (100 + 10) * 0.20
    });

    it('uses custom shipping tax rate when provided', function () {
        $cart = new Cart([
            'tax_data' => [
                'code' => 'CUSTOM_SHIPPING_TAX',
                'rate' => 0.20,
                'apply_to_shipping' => true,
                'shipping_rate' => 0.15, // Custom shipping rate
            ],
        ]);
        $cart->setRelation('items', collect([
            new CartItem(['price' => 100.0, 'quantity' => 1, 'product_id' => 'prod_1']),
        ]));

        $tax = $this->calculator->calculate($cart, 100.0, 10.0);

        expect($tax)->toBe(21.5); // (100 * 0.20) + (10 * 0.15) = 20 + 1.5
    });

    it('gets effective rate correctly', function () {
        $cart = new Cart([
            'tax_data' => [
                'rate' => 0.20,
                'conditions' => [
                    'rates_per_item' => [
                        'prod_1' => 0.15,
                    ],
                    'rates_per_category' => [
                        'books' => 0.05,
                    ],
                ],
            ],
        ]);

        expect($this->calculator->getEffectiveRate($cart, null, 'prod_1'))->toBe(0.15)
            ->and($this->calculator->getEffectiveRate($cart, 'books'))->toBe(0.05)
            ->and($this->calculator->getEffectiveRate($cart, 'electronics'))->toBe(0.20)
            ->and($this->calculator->getEffectiveRate($cart))->toBe(0.20);
    });
});
