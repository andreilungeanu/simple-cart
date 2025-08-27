<?php

namespace AndreiLungeanu\SimpleCart\Tests\Performance;

use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\Facades\SimpleCart as Cart;
use AndreiLungeanu\SimpleCart\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Benchmark;

class CartBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group benchmark
     */
    public function test_cart_performance_suite()
    {
        $iterations = 100;

        $result = Benchmark::measure(
            [
                'Adding 100 Items' => fn () => $this->benchmarkAddItems(100),
                'Calculating Total with 50 Items + Discount' => fn () => $this->benchmarkCalculateTotal(50),
                'Persistence Save/Find with 20 Items' => fn () => $this->benchmarkPersistence(20),
                'Get Cart Data Array with 50 Items' => fn () => $this->benchmarkGetArray(50),
            ],
            $iterations
        );

        foreach ($result as $operation => $milliseconds) {
            $this->assertIsNumeric($milliseconds, "Benchmark '$operation' did not return numeric time.");
        }
    }

    private function benchmarkAddItems(int $count): void
    {
        $cart = Cart::create();
        $cartId = $cart->getId();
        for ($i = 0; $i < $count; $i++) {
            Cart::addItem($cartId, new CartItemDTO(
                id: (string) $i,
                name: "Product $i",
                price: 10.00,
                quantity: 1
            ));
        }
    }

    private function benchmarkCalculateTotal(int $itemCount): void
    {
        $cartId = $this->createCartWithItemsFacade($itemCount);
        Cart::applyDiscount($cartId, new \AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO(code: 'TEST10', type: 'fixed', value: 1.0));
        Cart::total($cartId);
    }

    private function benchmarkPersistence(int $itemCount): void
    {
        $cartId = $this->createCartWithItemsFacade($itemCount);
        Cart::find($cartId);
    }

    private function benchmarkGetArray(int $itemCount): void
    {
        $cartId = $this->createCartWithItemsFacade($itemCount);
        $cart = Cart::find($cartId);
        $instance = $cart->getInstance();
        $items = $instance ? $instance->getItems() : collect([]);
        $id = $cart->getId();
    }

    private function createCartWithItemsFacade(int $count): string
    {
        $cart = Cart::create();
        $cartId = $cart->getId();
        for ($i = 0; $i < $count; $i++) {
            Cart::addItem($cartId, new CartItemDTO(
                id: (string) $i,
                name: "Product $i",
                price: 10.00,
                quantity: 1
            ));
        }

        return $cartId;
    }
}

/**
 * @group benchmark
 */
test('cart add operations remain performant with many items', function () {
    $cartWrapper = Cart::create();
    $cartId = $cartWrapper->getId();
    $startTime = microtime(true);

    for ($i = 0; $i < 100; $i++) {
        $cartWrapper->addItem(new CartItemDTO(
            id: (string) $i,
            name: "Product $i",
            price: 9.99,
            quantity: 1,
            metadata: ['sku' => "SKU$i"]
        ));
    }

    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000;

    expect($duration)->toBeNumeric();
    $loadedCartWrapper = Cart::find($cartId);
    expect($loadedCartWrapper->getInstance()->getItems())->toHaveCount(100);
})->group('benchmark');

/**
 * @group benchmark
 */
test('large cart calculation performance', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartId = $cartWrapper->getId();
    for ($i = 0; $i < 100; $i++) {
        $cartWrapper->addItem(new CartItemDTO(
            id: (string) $i,
            name: "Product $i",
            price: 99.99,
            quantity: rand(1, 5),
            metadata: []
        ));
    }

    $startTime = microtime(true);
    Cart::total($cartId);
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;

    expect($executionTime)->toBeNumeric();
})->group('benchmark');

/**
 * @group benchmark
 */
test('cart find and access performance', function () {
    $cartWrapper = Cart::create(taxZone: 'RO');
    $cartId = $cartWrapper->getId();
    for ($i = 0; $i < 50; $i++) {
        $cartWrapper->addItem(new CartItemDTO(
            id: (string) $i,
            name: "Product $i",
            price: 99.99,
            quantity: 1,
            category: 'test',
            metadata: ['weight' => rand(1, 10)]
        ));
    }

    $startTime = microtime(true);
    $loadedCartWrapper = Cart::find($cartId);
    $items = $loadedCartWrapper->getInstance()->getItems();
    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000;

    expect($duration)->toBeNumeric();
})->group('benchmark');
