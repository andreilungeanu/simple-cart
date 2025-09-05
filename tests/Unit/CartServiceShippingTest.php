<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use AndreiLungeanu\SimpleCart\Services\CartService;

describe('CartService - Dynamic Shipping', function () {

    beforeEach(function () {
        $this->cartService = app(CartService::class);
    });

    it('can apply shipping with complete data', function () {
        $cart = createTestCart(1);

        $shippingData = [
            'method_name' => 'UPS Ground',
            'cost' => 12.99,
            'carrier' => 'UPS',
            'estimated_delivery' => '3-5 business days',
        ];

        $this->cartService->applyShipping($cart, $shippingData);
        $cart->refresh();

        expect($cart->shipping_data)->toBe($shippingData);
    });

    it('can apply shipping with minimal required data', function () {
        $cart = createTestCart(1);

        $shippingData = [
            'method_name' => 'Standard Shipping',
            'cost' => 5.99,
        ];

        $this->cartService->applyShipping($cart, $shippingData);
        $cart->refresh();

        expect($cart->shipping_data)->toBe($shippingData);
    });

    it('validates required shipping data fields', function () {
        $cart = createTestCart(1);

        // Missing method_name
        expect(fn () => $this->cartService->applyShipping($cart, [
            'cost' => 10.00,
        ]))->toThrow(CartException::class, 'Shipping data must include method_name and cost');

        // Missing cost
        expect(fn () => $this->cartService->applyShipping($cart, [
            'method_name' => 'Express',
        ]))->toThrow(CartException::class, 'Shipping data must include method_name and cost');
    });

    it('validates shipping cost is numeric and non-negative', function () {
        $cart = createTestCart(1);

        // Non-numeric cost
        expect(fn () => $this->cartService->applyShipping($cart, [
            'method_name' => 'Express',
            'cost' => 'invalid',
        ]))->toThrow(CartException::class, 'Shipping cost must be a non-negative number');

        // Negative cost
        expect(fn () => $this->cartService->applyShipping($cart, [
            'method_name' => 'Express',
            'cost' => -5.99,
        ]))->toThrow(CartException::class, 'Shipping cost must be a non-negative number');
    });

    it('accepts zero cost shipping', function () {
        $cart = createTestCart(1);

        $shippingData = [
            'method_name' => 'Free Shipping',
            'cost' => 0,
        ];

        $this->cartService->applyShipping($cart, $shippingData);
        $cart->refresh();

        expect($cart->shipping_data)->toBe($shippingData);
    });

    it('accepts different numeric cost formats', function () {
        $cart = createTestCart(1);

        // Integer
        $this->cartService->applyShipping($cart, [
            'method_name' => 'Test 1',
            'cost' => 10,
        ]);
        $cart->refresh();
        expect($cart->shipping_data['cost'])->toBe(10);

        // Float
        $this->cartService->applyShipping($cart, [
            'method_name' => 'Test 2',
            'cost' => 12.50,
        ]);
        $cart->refresh();
        expect($cart->shipping_data['cost'])->toBe(12.50);

        // String numeric
        $this->cartService->applyShipping($cart, [
            'method_name' => 'Test 3',
            'cost' => '15.75',
        ]);
        $cart->refresh();
        expect($cart->shipping_data['cost'])->toBe('15.75'); // Stored as-is, validated as numeric
    });

    it('can remove shipping', function () {
        $cart = createTestCart(1);

        // First apply shipping
        $this->cartService->applyShipping($cart, [
            'method_name' => 'Express',
            'cost' => 15.99,
        ]);
        $cart->refresh();
        expect($cart->shipping_data)->not()->toBeNull();

        // Then remove it
        $this->cartService->removeShipping($cart);
        $cart->refresh();
        expect($cart->shipping_data)->toBeNull();
    });

    it('can get applied shipping', function () {
        $cart = createTestCart(1);

        $shippingData = [
            'method_name' => 'FedEx Overnight',
            'cost' => 29.99,
            'carrier' => 'FedEx',
        ];

        $this->cartService->applyShipping($cart, $shippingData);
        $appliedShipping = $this->cartService->getAppliedShipping($cart);

        expect($appliedShipping)->toBe($shippingData);
    });

    it('returns null when no shipping is applied', function () {
        $cart = createTestCart(1);

        $appliedShipping = $this->cartService->getAppliedShipping($cart);

        expect($appliedShipping)->toBeNull();
    });

    it('clears shipping data when cart is cleared', function () {
        $cart = createTestCart(1);
        $this->cartService->addItem($cart, [
            'product_id' => 'TEST-1',
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 1,
        ]);

        $this->cartService->applyShipping($cart, [
            'method_name' => 'Express',
            'cost' => 12.99,
        ]);
        $cart->refresh();
        expect($cart->shipping_data)->not()->toBeNull();

        $this->cartService->clear($cart);
        $cart->refresh();

        expect($cart->items->count())->toBe(0)
            ->and($cart->shipping_data)->toBeNull();
    });

    it('includes shipping in cart calculations', function () {
        $cart = createTestCart(1);

        $this->cartService->addItem($cart, [
            'product_id' => 'TEST-1',
            'name' => 'Test Product',
            'price' => 50.00,
            'quantity' => 1,
        ]);

        $this->cartService->applyShipping($cart, [
            'method_name' => 'Standard',
            'cost' => 7.99,
        ]);

        // Set tax zone to null to avoid tax calculations
        $cart->update(['tax_zone' => null]);

        $subtotal = $this->cartService->calculateSubtotal($cart);
        $shipping = $this->cartService->calculateShipping($cart);
        $total = $this->cartService->calculateTotal($cart);

        expect($subtotal)->toBe(50.00)
            ->and($shipping)->toBe(7.99)
            ->and($total)->toBe(57.99);
    });

    it('applies free shipping when threshold is met', function () {
        $cart = createTestCart(1);

        // Add item that meets free shipping threshold ($100)
        $this->cartService->addItem($cart, [
            'product_id' => 'EXPENSIVE-ITEM',
            'name' => 'Expensive Item',
            'price' => 150.00,
            'quantity' => 1,
        ]);

        $this->cartService->applyShipping($cart, [
            'method_name' => 'Standard',
            'cost' => 9.99,
        ]);

        // Set tax zone to null to avoid tax calculations
        $cart->update(['tax_zone' => null]);

        $shipping = $this->cartService->calculateShipping($cart);
        $total = $this->cartService->calculateTotal($cart);

        expect($shipping)->toBe(0.0) // Free shipping applied
            ->and($total)->toBe(150.00); // No shipping cost
    });
});
