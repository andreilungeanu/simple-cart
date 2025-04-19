<?php

namespace AndreiLungeanu\SimpleCart\Tests\Performance;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\SimpleCart;
use AndreiLungeanu\SimpleCart\Tests\TestCase;
use Illuminate\Support\Benchmark;

class CartBenchmarkTest extends TestCase
{
    public function test_cart_performance()
    {
        $result = Benchmark::measure(
            [
                'Adding 100 Items' => fn () => $this->benchmarkAddItems(100),
                'Calculating Total with Discounts' => fn () => $this->benchmarkCalculateTotal(),
                'Persistence Operations' => fn () => $this->benchmarkPersistence(),
            ],
            1000
        );

        // Assert operations complete within acceptable time
        foreach ($result as $operation => $milliseconds) {
            $this->assertLessThan(
                100,
                $milliseconds,
                "Operation '$operation' took too long: {$milliseconds}ms"
            );
        }
    }

    private function benchmarkAddItems(int $count): void
    {
        $cart = app(SimpleCart::class)->create();

        for ($i = 0; $i < $count; $i++) {
            $cart->addItem(new CartItemDTO(
                id: (string) $i,
                name: "Product $i",
                price: 10.00,
                quantity: 1
            ));
        }
    }

    private function benchmarkCalculateTotal(): void
    {
        $cart = $this->createCartWithItems(50);
        $cart->applyDiscount('TEST10');
        $cart->total();
    }

    private function benchmarkPersistence(): void
    {
        $cart = $this->createCartWithItems(20);
        $cart->save();
        $cart->find($cart->get()->id);
    }

    private function createCartWithItems(int $count): SimpleCart
    {
        $cart = app(SimpleCart::class)->create();

        for ($i = 0; $i < $count; $i++) {
            $cart->addItem(new CartItemDTO(
                id: (string) $i,
                name: "Product $i",
                price: 10.00,
                quantity: 1
            ));
        }

        return $cart;
    }
}

test('cart operations remain performant with many items', function () {
    $cart = app(SimpleCart::class)->create();
    $startTime = microtime(true);

    // Add 100 items
    for ($i = 0; $i < 100; $i++) {
        $cart->addItem(new CartItemDTO(
            id: (string) $i,
            name: "Product $i",
            price: 9.99,
            quantity: 1,
            metadata: ['sku' => "SKU$i"]
        ));
    }

    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000; // ms

    expect($duration)->toBeLessThan(100) // Should take less than 100ms
        ->and($cart->get()->getItems())->toHaveCount(100);
});

test('large cart calculation performance', function () {
    $startTime = microtime(true);
    $cart = new CartDTO(taxZone: 'RO');

    // Add 100 items
    for ($i = 0; $i < 100; $i++) {
        $cart->addItem(new CartItemDTO(
            id: (string) $i,
            name: "Product $i",
            price: 99.99,
            quantity: rand(1, 5),
            metadata: []
        ));
    }

    $cart->calculateTotal();
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // ms

    expect($executionTime)->toBeLessThan(100); // Should complete in under 100ms
});

test('cart serialization performance', function () {
    $cart = new CartDTO(taxZone: 'RO');

    // Add items with all features
    for ($i = 0; $i < 50; $i++) {
        $cart->addItem(new CartItemDTO(
            id: (string) $i,
            name: "Product $i",
            price: 99.99,
            quantity: 1,
            category: 'test',
            metadata: ['weight' => rand(1, 10)]
        ));
    }

    $startTime = microtime(true);
    $cart->toArray();
    $endTime = microtime(true);

    expect(($endTime - $startTime) * 1000)->toBeLessThan(50); // Under 50ms
});
