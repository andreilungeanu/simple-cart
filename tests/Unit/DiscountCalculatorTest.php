<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use AndreiLungeanu\SimpleCart\Services\Calculators\DiscountCalculator;
use AndreiLungeanu\SimpleCart\Services\Calculators\ShippingCalculator;

describe('DiscountCalculator', function () {

    beforeEach(function () {
        // Create a test configuration with discount system settings
        $testConfig = [
            'storage' => ['ttl_days' => 30],
            'shipping' => [
                'settings' => [
                    'free_shipping_threshold' => 100.0,
                    'methods' => [
                        'standard' => [
                            'name' => 'Standard Shipping',
                            'cost' => 5.99,
                            'type' => 'flat',
                        ],
                        'express' => [
                            'name' => 'Express Shipping',
                            'cost' => 15.99,
                            'type' => 'flat',
                        ],
                    ],
                ],
            ],
            'tax' => ['settings' => ['zones' => []], 'default_zone' => 'US'],
            'discounts' => [
                'allow_stacking' => true, // Enable stacking for tests
                'max_discount_codes' => 5, // Allow more codes
            ],
        ];

        $this->config = CartConfiguration::fromConfig($testConfig);
        $this->shippingCalculator = new ShippingCalculator($this->config);
        $this->calculator = new DiscountCalculator($this->config);
    });

    it('calculates fixed discount correctly', function () {
        $cart = Cart::factory()
            ->hasItems(1, [
                'product_id' => 'TEST-PROD',
                'name' => 'Test Product',
                'price' => 60.00,
                'quantity' => 1,
            ])
            ->create();

        $subtotal = $cart->subtotal;

        // Debug the subtotal
        expect($subtotal)->toBe(60.0);

        // Apply discount using new system - set after refresh
        $discountData = [
            'SAVE10' => [
                'code' => 'SAVE10',
                'type' => 'fixed',
                'value' => 10, // Use integer to match what would come from app
                'conditions' => ['minimum_amount' => 50],
            ],
        ];
        $cart->discount_data = $discountData;

        // CRITICAL: Make sure the cart has items relationship loaded after setting discount data
        $cart->load('items');

        // Verify discount data is set
        expect($cart->discount_data)->not()->toBeNull();
        expect($cart->discount_data)->toHaveKey('SAVE10');
        expect($cart->discount_data['SAVE10']['value'])->toBe(10);
        expect($cart->discount_data['SAVE10']['conditions']['minimum_amount'])->toBe(50);

        // Manual validation test - the subtotal (60) should be >= minimum (50)
        $conditions = $cart->discount_data['SAVE10']['conditions'];
        expect($subtotal >= $conditions['minimum_amount'])->toBeTrue();

        // Now test the calculator
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(10.0);
    });

    it('calculates percentage discount correctly', function () {
        $cart = Cart::factory()
            ->withDiscounts(['PERCENT15'])
            ->hasItems(1, [
                'product_id' => 'TEST-PROD',
                'name' => 'Test Product',
                'price' => 100.00, // Above $75 minimum
                'quantity' => 1,
            ])
            ->create();

        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(15.0); // 15% of $100 = $15
    });

    it('respects minimum order requirements', function () {
        $cart = Cart::factory()
            ->withDiscounts(['SAVE20'])
            ->hasItems(1, [
                'product_id' => 'TEST-PROD',
                'name' => 'Test Product',
                'price' => 50.00, // Below $100 minimum
                'quantity' => 1,
            ])
            ->create();

        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(0.0); // Should not apply discount
    });

    it('handles free shipping discount type', function () {
        $cart = Cart::factory()
            ->withDiscounts(['FREESHIP'])
            ->withShipping(['cost' => 5.99, 'method_name' => 'Standard Shipping'])
            ->create();

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 'TEST-PROD',
            'name' => 'Test Product',
            'price' => 60.00,
            'quantity' => 1,
        ]);

        $cart->refresh(['items']);
        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(0.0); // DiscountCalculator returns 0.0, ShippingCalculator handles free shipping logic
    });

    it('handles category-based discounts', function () {
        $cart = Cart::factory()
            ->withDiscounts(['BOOKS20'])
            ->hasItems(1, [
                'product_id' => 'BOOK-1',
                'name' => 'Laravel Book',
                'price' => 45.00,
                'quantity' => 1,
                'category' => 'books',
            ])
            ->create();

        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(9.0); // 20% of $45 = $9
    });

    it('handles item-specific discounts', function () {
        $cart = Cart::factory()
            ->withDiscounts(['LAPTOP_BULK'])
            ->hasItems(1, [
                'product_id' => 'laptop_pro',
                'name' => 'Gaming Laptop',
                'price' => 999.00,
                'quantity' => 2,
            ])
            ->create();

        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(50.0); // $50 off laptop
    });

    it('returns zero when no discount data exists', function () {
        $cart = Cart::factory()
            ->hasItems(1, [
                'product_id' => 'TEST-PROD',
                'name' => 'Test Product',
                'price' => 100.00,
                'quantity' => 1,
            ])
            ->create();

        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(0.0);
    });

    it('cant discount more than subtotal', function () {
        $discountData = [
            'SAVE20' => [
                'code' => 'SAVE20',
                'type' => 'fixed',
                'value' => 20.0,
                'conditions' => [], // No minimum
            ],
        ];

        $cart = Cart::factory()
            ->state(['discount_data' => $discountData])
            ->hasItems(1, [
                'product_id' => 'TEST-PROD',
                'name' => 'Test Product',
                'price' => 15.00, // Less than $20 discount
                'quantity' => 1,
            ])
            ->create();

        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(15.0); // Should cap at subtotal, not exceed it
    });

    it('applies percentage discount with unit cap for specific item', function () {
        $discountData = [
            'CAP50' => [
                'code' => 'CAP50',
                'type' => 'percentage',
                'value' => 50.0,
                'conditions' => [
                    'item_id' => 'prod_123',
                    'min_quantity' => 2,
                    'max_discounted_units' => 1,
                ],
            ],
        ];

        $cart = Cart::factory()
            ->state(['discount_data' => $discountData])
            ->hasItems(1, [
                'product_id' => 'prod_123',
                'name' => 'Test Product',
                'price' => 100.00,
                'quantity' => 2,
            ])
            ->create();

        $subtotal = $cart->subtotal; // 200
        $discount = $this->calculator->calculate($cart, $subtotal);

        // 50% off up to 1 unit at $100 => $50
        expect($discount)->toBe(50.0);
    });

    it('applies cap to cheapest eligible units in category (percentage)', function () {
        $discountData = [
            'SHOES50' => [
                'code' => 'SHOES50',
                'type' => 'percentage',
                'value' => 50.0,
                'conditions' => [
                    'category' => 'shoes',
                    'min_quantity' => 2,
                    'max_discounted_units' => 1,
                ],
            ],
        ];

        $cart = Cart::factory()
            ->state(['discount_data' => $discountData])
            ->hasItems(1, [
                'product_id' => 'shoe_low',
                'name' => 'Shoe Low',
                'price' => 80.00,
                'quantity' => 1,
                'category' => 'shoes',
            ])
            ->hasItems(1, [
                'product_id' => 'shoe_high',
                'name' => 'Shoe High',
                'price' => 120.00,
                'quantity' => 1,
                'category' => 'shoes',
            ])
            ->create();

        $subtotal = $cart->subtotal; // 200
        $discount = $this->calculator->calculate($cart, $subtotal);

        // Cap 1 unit -> choose cheapest ($80) * 50% = $40
        expect($discount)->toBe(40.0);
    });

    it('caps fixed discount by capped units subtotal', function () {
        $discountData = [
            'FIXED60' => [
                'code' => 'FIXED60',
                'type' => 'fixed',
                'value' => 60.0,
                'conditions' => [
                    'item_id' => 'prod_123',
                    'min_quantity' => 1,
                    'max_discounted_units' => 1,
                ],
            ],
        ];

        $cart = Cart::factory()
            ->state(['discount_data' => $discountData])
            ->hasItems(1, [
                'product_id' => 'prod_123',
                'name' => 'Item A',
                'price' => 30.00,
                'quantity' => 2,
            ])
            ->create();

        $subtotal = $cart->subtotal; // 60
        $discount = $this->calculator->calculate($cart, $subtotal);

        // Cap 1 unit -> capped subtotal $30; fixed 60 should cap at 30
        expect($discount)->toBe(30.0);
    });

    it('without unit cap applies to all eligible items', function () {
        $discountData = [
            'HALFALL' => [
                'code' => 'HALFALL',
                'type' => 'percentage',
                'value' => 50.0,
                'conditions' => [
                    'item_id' => 'prod_123',
                    'min_quantity' => 2,
                ],
            ],
        ];

        $cart = Cart::factory()
            ->state(['discount_data' => $discountData])
            ->hasItems(1, [
                'product_id' => 'prod_123',
                'name' => 'Test Product',
                'price' => 100.00,
                'quantity' => 2,
            ])
            ->create();

        $subtotal = $cart->subtotal; // 200
        $discount = $this->calculator->calculate($cart, $subtotal);

        // No cap -> 50% of entire eligible subtotal ($200) = $100
        expect($discount)->toBe(100.0);
    });
});
