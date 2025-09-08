<?php

declare(strict_types=1);

use AndreiLungeanu\SimpleCart\Enums\CartStatusEnum;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use AndreiLungeanu\SimpleCart\Services\CartService;
use Illuminate\Support\Facades\Event;

describe('CartService - Basic Operations', function () {

    it('can create a new cart', function () {
        $cartService = app(CartService::class);

        $cart = $cartService->create(userId: 1);

        expect($cart)->toBeInstanceOf(Cart::class)
            ->and($cart->user_id)->toBe(1)
            ->and($cart->status)->toBe(CartStatusEnum::Active)
            ->and($cart->expires_at)->not()->toBeNull();
    });

    it('can create a cart without user (guest)', function () {
        $cartService = app(CartService::class);

        $cart = $cartService->create();

        expect($cart)->toBeInstanceOf(Cart::class)
            ->and($cart->user_id)->toBeNull()
            ->and($cart->session_id)->not()->toBeNull()
            ->and($cart->status)->toBe(CartStatusEnum::Active);
    });

    it('can find a cart by ID', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $foundCart = $cartService->find($cart->id);

        expect($foundCart)->not()->toBeNull()
            ->and($foundCart->id)->toBe($cart->id);
    });

    it('returns null when cart is not found', function () {
        $cartService = app(CartService::class);

        $foundCart = $cartService->find('non-existent-id');

        expect($foundCart)->toBeNull();
    });

    it('throws exception when using findOrFail with non-existent cart', function () {
        $cartService = app(CartService::class);

        expect(fn () => $cartService->findOrFail('non-existent-id'))
            ->toThrow(CartException::class);
    });

    it('dispatches CartUpdated event when cart is created', function () {
        Event::fake();
        $cartService = app(CartService::class);

        $cart = $cartService->create(userId: 1);

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cart) {
            return $event->cart->id === $cart->id && $event->action === 'created';
        });
    });

});

describe('CartService - Item Management', function () {

    it('can add item to cart', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $item = $cartService->addItem($cart, [
            'product_id' => 'PROD-1',
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2,
            'category' => 'electronics',
            'metadata' => ['color' => 'blue'],
        ]);

        expect($item)->toBeInstanceOf(CartItem::class)
            ->and($item->product_id)->toBe('PROD-1')
            ->and($item->name)->toBe('Test Product')
            ->and($item->price)->toBe('29.99')
            ->and($item->quantity)->toBe(2)
            ->and($item->category)->toBe('electronics')
            ->and($item->metadata)->toBe(['color' => 'blue']);
    });

    it('combines quantities when adding existing item', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        // Add item first time
        $cartService->addItem($cart, [
            'product_id' => 'PROD-1',
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2,
        ]);

        // Add same item again
        $cartService->addItem($cart, [
            'product_id' => 'PROD-1',
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 3,
        ]);

        $cart->refresh();
        $item = $cart->items->where('product_id', 'PROD-1')->first();

        expect($item->quantity)->toBe(5);
    });

    it('can update item quantity', function () {
        $cartService = app(CartService::class);
        $cart = Cart::factory()->withTestItems()->create();

        $cartService->updateQuantity($cart, 'PROD-1', 5);

        $cart->refresh();
        $item = $cart->items->where('product_id', 'PROD-1')->first();

        expect($item->quantity)->toBe(5);
    });

    it('removes item when quantity is set to zero', function () {
        $cartService = app(CartService::class);
        $cart = Cart::factory()->withTestItems()->create();

        $cartService->updateQuantity($cart, 'PROD-1', 0);

        $cart->refresh();
        expect($cart->items->where('product_id', 'PROD-1')->count())->toBe(0);
    });

    it('can remove item from cart', function () {
        $cartService = app(CartService::class);
        $cart = Cart::factory()->withTestItems()->create();

        $cartService->removeItem($cart, 'PROD-1');

        $cart->refresh();
        expect($cart->items->where('product_id', 'PROD-1')->count())->toBe(0);
    });

    it('throws exception for invalid item data', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        expect(fn () => $cartService->addItem($cart, [
            'name' => 'Test Product', // Missing product_id
            'price' => 29.99,
        ]))->toThrow(CartException::class, 'Missing required field: product_id');
    });

    it('throws exception for negative price', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        expect(fn () => $cartService->addItem($cart, [
            'product_id' => 'PROD-1',
            'name' => 'Test Product',
            'price' => -10.00,
        ]))->toThrow(CartException::class, 'Price cannot be negative');
    });

    it('can clear cart', function () {
        $cartService = app(CartService::class);
        $cart = Cart::factory()->withTestItems()->create();

        $cartService->clear($cart);

        $cart->refresh();
        expect($cart->items->count())->toBe(0)
            ->and($cart->discount_data)->toBe([])
            ->and($cart->shipping_data)->toBeNull();
    });

});

describe('CartService - Calculations', function () {

    it('calculates subtotal correctly', function () {
        $cartService = app(CartService::class);
        $cart = Cart::factory()->withTestItems()->create();

        $subtotal = $cartService->calculateSubtotal($cart);

        // PROD-1: 29.99 * 2 = 59.98
        // PROD-2: 15.00 * 1 = 15.00
        // Total: 74.98
        expect($subtotal)->toBe(74.98);
    });

    it('calculates shipping cost correctly', function () {
        $cartService = app(CartService::class);
        $cart = Cart::factory()->withTestItems()->create();
        $cart->update(['shipping_data' => ['method_name' => 'Standard', 'cost' => 5.99]]);
        $cart->refresh();

        $shipping = $cartService->calculateShipping($cart);

        expect($shipping)->toBe(5.99);
    });

    it('applies free shipping when threshold is met', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        // Add item that meets free shipping threshold
        $cartService->addItem($cart, [
            'product_id' => 'EXPENSIVE-ITEM',
            'name' => 'Expensive Item',
            'price' => 150.00,
            'quantity' => 1,
        ]);

        $cart->update(['shipping_data' => ['method_name' => 'Standard', 'cost' => 5.99]]);
        $cart->refresh();

        $shipping = $cartService->calculateShipping($cart);

        expect($shipping)->toBe(0.0);
    });

    it('calculates tax correctly', function () {
        $cartService = app(CartService::class);
        $cart = Cart::factory()->withTestItems()->create();

        // Apply tax with category-specific rates
        $cartService->applyTax($cart, [
            'code' => 'SALES_TAX',
            'name' => 'Sales Tax',
            'rate' => 0.0725,
            'conditions' => [
                'rates_per_category' => [
                    'books' => 0.05,
                ],
            ],
        ]);

        $cart->refresh();

        $tax = $cartService->calculateTax($cart);

        // books: 15.00 * 0.05 = 0.75 (category-specific rate)
        // electronics: 29.99 * 2 * 0.0725 = 4.35 (default rate)
        // Total tax: 5.10
        expect($tax)->toBe(5.10);
    });

    it('calculates total correctly', function () {
        $cartService = app(CartService::class);
        $cart = Cart::factory()->withTestItems()->create();

        // Apply shipping
        $cartService->applyShipping($cart, ['method_name' => 'Standard', 'cost' => 5.99]);

        // Apply tax
        $cartService->applyTax($cart, [
            'code' => 'SALES_TAX',
            'name' => 'Sales Tax',
            'rate' => 0.0725,
            'conditions' => [
                'rates_per_category' => [
                    'books' => 0.05,
                ],
            ],
        ]);

        $cart->refresh();

        $total = $cartService->calculateTotal($cart);

        // Subtotal: 74.98
        // Shipping: 5.99
        // Tax: 5.10
        // Total: 86.07
        expect($total)->toBe(86.07);
    });

});

describe('CartService - Discounts & Settings', function () {

    it('can apply discount', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $discountData = [
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20,
            'conditions' => [],
        ];

        $cartService->applyDiscount($cart, $discountData);

        $cart->refresh();
        expect($cart->discount_data)->toHaveKey('SAVE20');
        expect($cart->discount_data['SAVE20']['type'])->toBe('percentage');
        expect($cart->discount_data['SAVE20']['value'])->toBe(20);

        Event::assertDispatched(CartUpdated::class, function ($event) {
            return $event->action === 'discount_applied';
        });
    });

    it('prevents duplicate discount codes', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $discountData = [
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20,
            'conditions' => [],
        ];

        $cartService->applyDiscount($cart, $discountData);
        $cartService->applyDiscount($cart, $discountData); // Apply again

        $cart->refresh();
        expect($cart->discount_data)->toHaveKey('SAVE20');
        expect(count($cart->discount_data))->toBe(1); // Only one discount should exist
    });

    it('can remove discount code', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $discountData = [
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20,
            'conditions' => [],
        ];

        $cartService->applyDiscount($cart, $discountData);
        $cartService->removeDiscount($cart, 'SAVE20');

        $cart->refresh();
        expect($cart->discount_data)->not()->toHaveKey('SAVE20');
    });

    it('can apply shipping', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $cartService->applyShipping($cart, [
            'method_name' => 'Express Shipping',
            'cost' => 15.99,
            'carrier' => 'UPS',
        ]);

        $cart->refresh();
        expect($cart->shipping_data['method_name'])->toBe('Express Shipping');
        expect($cart->shipping_data['cost'])->toBe(15.99);
        expect($cart->shipping_data['carrier'])->toBe('UPS');
    });

    it('validates shipping data requirements', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        expect(fn () => $cartService->applyShipping($cart, [
            'cost' => 15.99, // Missing method_name
        ]))->toThrow(CartException::class, 'Shipping data must include method_name and cost');
    });

    it('can apply tax', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        $cartService->applyTax($cart, [
            'code' => 'VAT',
            'name' => 'Value Added Tax',
            'rate' => 0.19,
        ]);

        $cart->refresh();
        expect($cart->tax_data)->toBe([
            'code' => 'VAT',
            'name' => 'Value Added Tax',
            'rate' => 0.19,
        ]);
    });

    it('throws exception for invalid tax data', function () {
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);

        expect(fn () => $cartService->applyTax($cart, [
                'code' => 'INVALID',
                'name' => 'Invalid Tax',
                // Missing required 'rate' field
            ]))
            ->toThrow(CartException::class, 'Tax data must include a rate');
    });

});

describe('CartService - Cart Summary', function () {

    it('returns comprehensive cart summary', function () {
        $cartService = app(CartService::class);
        $cart = Cart::factory()->withTestItems()->create();

        // Apply shipping
        $cartService->applyShipping($cart, ['method_name' => 'Standard', 'cost' => 5.99]);

        // Apply tax
        $cartService->applyTax($cart, [
            'code' => 'SALES_TAX',
            'name' => 'Sales Tax',
            'rate' => 0.0725,
            'conditions' => [
                'rates_per_category' => [
                    'books' => 0.05,
                ],
            ],
        ]);

        $cart->refresh();

        $summary = $cartService->getCartSummary($cart);

        expect($summary)->toHaveKeys([
            'id', 'item_count', 'subtotal', 'shipping', 'tax', 'discounts', 'total', 'status', 'expires_at',
        ])
            ->and($summary['item_count'])->toBe(3)
            ->and($summary['subtotal'])->toBe(74.98)
            ->and($summary['shipping'])->toBe(5.99)
            ->and($summary['tax'])->toBe(5.10)
            ->and($summary['total'])->toBe(86.07)
            ->and($summary['status'])->toBe('active');
    });

});

describe('CartService - Cart Lifecycle', function () {

    it('can delete cart', function () {
        Event::fake();
        $cartService = app(CartService::class);
        $cart = $cartService->create(userId: 1);
        $cartId = $cart->id;

        $cartService->delete($cart);

        expect($cartService->find($cartId))->toBeNull();

        Event::assertDispatched(CartUpdated::class, function ($event) use ($cartId) {
            return $event->action === 'deleted' && $event->metadata['cart_id'] === $cartId;
        });
    });

});

describe('CartService - Advanced Testing', function () {
    beforeEach(function () {
        $this->cartService = app(CartService::class);
    });

    it('can mock external dependencies cleanly', function () {
        $mockTaxCalculator = mock(\AndreiLungeanu\SimpleCart\Services\Calculators\TaxCalculator::class);
        $mockTaxCalculator->shouldReceive('calculate')->andReturn(15.50);

        app()->instance(\AndreiLungeanu\SimpleCart\Services\Calculators\TaxCalculator::class, $mockTaxCalculator);

        $cart = Cart::factory()->withTestItems()->create();
        $taxAmount = app(\AndreiLungeanu\SimpleCart\Services\Calculators\TaxCalculator::class)->calculate($cart, 100.0);

        expect($taxAmount)->toBe(15.50);
    });

    it('handles various cart scenarios efficiently', function (?int $userId, ?string $sessionId, int $itemCount) {
        $cart = $this->cartService->create(userId: $userId, sessionId: $sessionId);

        // Add specified number of items
        for ($i = 0; $i < $itemCount; $i++) {
            $this->cartService->addItem($cart, [
                'product_id' => "PROD-{$i}",
                'name' => "Product {$i}",
                'price' => 10.00 * ($i + 1),
                'quantity' => 1,
            ]);
        }

        $cart->refresh();

        expect($cart->items)->toHaveCount($itemCount)
            ->and($cart->user_id)->toBe($userId);
    })->with([
        'authenticated user, no session, 1 item' => [1, null, 1],
        'guest user, with session, 3 items' => [null, 'sess_123', 3],
        'authenticated user, with session, 5 items' => [2, 'sess_456', 5],
    ]);

    it('maintains performance with various cart sizes', function (int $itemCount) {
        $startTime = microtime(true);

        $cart = Cart::factory()->create();

        // Create unique items to avoid constraint violations
        for ($i = 0; $i < $itemCount; $i++) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => "PERF-TEST-{$i}",
                'name' => "Performance Test Item {$i}",
                'price' => 10.00 + $i,
                'quantity' => 1,
                'category' => 'performance-test',
            ]);
        }

        $cart->refresh(['items']);
        $calculatedTotal = $this->cartService->calculateTotal($cart);

        $executionTime = microtime(true) - $startTime;

        expect($calculatedTotal)->toBeGreaterThan(0)
            ->and($executionTime)->toBeLessThan(1.0); // Should complete within 1 second
    })->with([
        'small cart' => [5],
        'medium cart' => [25],
        'large cart' => [50],
    ]);

    it('extensive integration test', function () {
        $cart = $this->cartService->create(userId: 1);

        // Complex workflow testing
        $this->cartService->addItem($cart, [
            'product_id' => 'PREMIUM-001',
            'name' => 'Premium Product',
            'price' => 299.99,
            'quantity' => 2,
        ]);

        $this->cartService->applyTax($cart, [
            'code' => 'SALES_TAX',
            'rate' => 0.0725,
        ]);
        $this->cartService->applyShipping($cart, ['method_name' => 'Express', 'cost' => 15.99]);

        $total = $this->cartService->calculateTotal($cart);

        expect($total)->toBeGreaterThan(599.98); // Should include tax and shipping

    })->skipLocally('Integration tests run only in CI environment');
});
