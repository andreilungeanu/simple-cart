<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
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
        $this->calculator = new DiscountCalculator($this->config, $this->shippingCalculator);
    });

    it('calculates fixed discount correctly', function () {
        $cart = createTestCart(1);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 'TEST-PROD',
            'name' => 'Test Product',
            'price' => 60.00,
            'quantity' => 1,
        ]);

        $cart->refresh(['items']);
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
        $cart = createTestCart(1);

        $discountData = [
            'PERCENT15' => [
                'code' => 'PERCENT15',
                'type' => 'percentage',
                'value' => 15.0,
                'conditions' => ['minimum_amount' => 75.0],
            ],
        ];
        $cart->update(['discount_data' => $discountData]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 'TEST-PROD',
            'name' => 'Test Product',
            'price' => 100.00, // Above $75 minimum
            'quantity' => 1,
        ]);

        $cart->refresh(['items']);
        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(15.0); // 15% of $100 = $15
    });

    it('respects minimum order requirements', function () {
        $cart = createTestCart(1);

        $discountData = [
            'SAVE20' => [
                'code' => 'SAVE20',
                'type' => 'fixed',
                'value' => 20.0,
                'conditions' => ['minimum_amount' => 100.0],
            ],
        ];
        $cart->update(['discount_data' => $discountData]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 'TEST-PROD',
            'name' => 'Test Product',
            'price' => 50.00, // Below $100 minimum
            'quantity' => 1,
        ]);

        $cart->refresh(['items']);
        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(0.0); // Should not apply discount
    });

    it('handles free shipping discount type', function () {
        $cart = createTestCart(1);
        $cart->update(['shipping_method' => 'standard']); // Set shipping method first

        $discountData = [
            'FREESHIP' => [
                'code' => 'FREESHIP',
                'type' => 'free_shipping',
                'value' => 0.0,
                'conditions' => [],
            ],
        ];
        $cart->update(['discount_data' => $discountData]);

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

        expect($discount)->toBe(5.99); // Should return shipping cost as discount
    });

    it('handles category-based discounts', function () {
        $cart = createTestCart(1);

        $discountData = [
            'BOOKS20' => [
                'code' => 'BOOKS20',
                'type' => 'percentage',
                'value' => 20.0,
                'conditions' => [
                    'category' => 'books',
                    'minimum_amount' => 30.0,
                ],
            ],
        ];
        $cart->update(['discount_data' => $discountData]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 'BOOK-1',
            'name' => 'Laravel Book',
            'price' => 45.00,
            'quantity' => 1,
            'category' => 'books',
        ]);

        $cart->refresh(['items']);
        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(9.0); // 20% of $45 = $9
    });

    it('handles item-specific discounts', function () {
        $cart = createTestCart(1);

        $discountData = [
            'LAPTOP_BULK' => [
                'code' => 'LAPTOP_BULK',
                'type' => 'fixed',
                'value' => 50.0,
                'conditions' => [
                    'item_id' => 'laptop_pro',
                    'min_quantity' => 2,
                ],
            ],
        ];
        $cart->update(['discount_data' => $discountData]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 'laptop_pro',
            'name' => 'Laptop Pro',
            'price' => 999.00,
            'quantity' => 2, // Meets minimum quantity
        ]);

        $cart->refresh(['items']);
        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(50.0); // $50 off laptop
    });

    it('returns zero when no discount data exists', function () {
        $cart = createTestCart(1);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 'TEST-PROD',
            'name' => 'Test Product',
            'price' => 100.00,
            'quantity' => 1,
        ]);

        $cart->refresh(['items']);
        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(0.0);
    });

    it('cant discount more than subtotal', function () {
        $cart = createTestCart(1);

        $discountData = [
            'SAVE20' => [
                'code' => 'SAVE20',
                'type' => 'fixed',
                'value' => 20.0,
                'conditions' => [], // No minimum
            ],
        ];
        $cart->update(['discount_data' => $discountData]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 'TEST-PROD',
            'name' => 'Test Product',
            'price' => 15.00, // Less than $20 discount
            'quantity' => 1,
        ]);

        $cart->refresh(['items']);
        $subtotal = $cart->subtotal;
        $discount = $this->calculator->calculate($cart, $subtotal);

        expect($discount)->toBe(15.0); // Should cap at subtotal, not exceed it
    });
});
