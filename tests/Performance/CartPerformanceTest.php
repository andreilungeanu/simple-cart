<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Services\CartService;

describe('Performance Tests', function () {

    it('can create many carts efficiently', function () {
        $cartService = app(CartService::class);
        $startTime = microtime(true);

        $carts = [];
        for ($i = 0; $i < 100; $i++) {
            $carts[] = $cartService->create(userId: $i);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        expect(count($carts))->toBe(100)
            ->and($executionTime)->toBeLessThan(2.0); // Should complete in under 2 seconds
    });

    it('can add many items to cart efficiently', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);
        $startTime = microtime(true);

        for ($i = 0; $i < 50; $i++) {
            $cartService->addItem($cart, [
                'product_id' => "PROD-{$i}",
                'name' => "Product {$i}",
                'price' => rand(1000, 9999) / 100, // Random price between 10.00 and 99.99
                'quantity' => rand(1, 5),
                'category' => ['electronics', 'books', 'clothing'][rand(0, 2)],
            ]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $cart->refresh();
        expect($cart->items->count())->toBe(50)
            ->and($executionTime)->toBeLessThan(1.0); // Should complete in under 1 second
    });

    it('can calculate totals for large cart efficiently', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        // Add many items
        for ($i = 0; $i < 100; $i++) {
            $cartService->addItem($cart, [
                'product_id' => "PROD-{$i}",
                'name' => "Product {$i}",
                'price' => rand(1000, 9999) / 100,
                'quantity' => rand(1, 3),
                'category' => ['electronics', 'books', 'food'][rand(0, 2)],
            ]);
        }

        $cart->update(['shipping_method' => 'standard', 'tax_zone' => 'US']);
        $cart->refresh();

        $startTime = microtime(true);

        // Perform calculations
        $subtotal = $cartService->calculateSubtotal($cart);
        $shipping = $cartService->calculateShipping($cart);
        $tax = $cartService->calculateTax($cart);
        $total = $cartService->calculateTotal($cart);
        $summary = $cartService->getCartSummary($cart);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        expect($subtotal)->toBeGreaterThan(0)
            ->and($total)->toBeGreaterThan($subtotal)
            ->and($summary)->toHaveKey('total')
            ->and($executionTime)->toBeLessThan(0.1); // Should complete in under 100ms
    });

    it('maintains consistent performance with database queries', function () {
        $cartService = app(CartService::class);
        $carts = [];

        // Create 10 carts with items
        for ($i = 0; $i < 10; $i++) {
            $cart = $cartService->create(userId: $i);
            for ($j = 0; $j < 5; $j++) {
                $cartService->addItem($cart, [
                    'product_id' => "PROD-{$i}-{$j}",
                    'name' => "Product {$i}-{$j}",
                    'price' => 29.99,
                    'quantity' => 1,
                ]);
            }
            $carts[] = $cart->id;
        }

        // Measure retrieval performance
        $startTime = microtime(true);

        foreach ($carts as $cartId) {
            $cart = $cartService->find($cartId);
            $summary = $cartService->getCartSummary($cart);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        expect($executionTime)->toBeLessThan(0.5); // Should complete in under 500ms
    });

    it('handles concurrent cart operations', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $startTime = microtime(true);

        // Simulate concurrent operations
        for ($i = 0; $i < 20; $i++) {
            $cartService->addItem($cart, [
                'product_id' => "CONCURRENT-{$i}",
                'name' => "Concurrent Item {$i}",
                'price' => 10.00,
                'quantity' => 1,
            ]);

            if ($i % 3 === 0) {
                $cartService->calculateTotal($cart->refresh());
            }

            if ($i % 5 === 0) {
                $cartService->updateQuantity($cart, "CONCURRENT-{$i}", 2);
            }
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        expect($executionTime)->toBeLessThan(1.0); // Should complete in under 1 second
    });

    it('memory usage remains reasonable', function () {
        $initialMemory = memory_get_usage();

        $cartService = app(CartService::class);
        $carts = [];

        for ($i = 0; $i < 50; $i++) {
            $cart = $cartService->create(userId: $i);
            for ($j = 0; $j < 10; $j++) {
                $cartService->addItem($cart, [
                    'product_id' => "MEMORY-{$i}-{$j}",
                    'name' => 'Memory Test Item',
                    'price' => 25.00,
                    'quantity' => 1,
                ]);
            }
            $carts[] = $cart;
        }

        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;

        // Should use less than 10MB for 50 carts with 10 items each
        expect($memoryUsed)->toBeLessThan(10 * 1024 * 1024); // 10MB
    });

});
