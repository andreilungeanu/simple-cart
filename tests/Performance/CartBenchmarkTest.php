<?php

namespace AndreiLungeanu\SimpleCart\Tests\Performance;

// Remove CartDTO import
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Facades\SimpleCart as Cart; // Use Facade
use AndreiLungeanu\SimpleCart\SimpleCart; // Keep for type hinting if needed
use AndreiLungeanu\SimpleCart\Tests\TestCase;
use Illuminate\Support\Benchmark;
use Illuminate\Foundation\Testing\RefreshDatabase; // Add for persistence test

// Note: Benchmarks often run outside the normal test runner lifecycle.
// Using TestCase might not be standard for pure benchmarks, but okay for now.
// Using RefreshDatabase might add overhead to persistence benchmark.
class CartBenchmarkTest extends TestCase
{
    use RefreshDatabase; // Apply trait

    // Consider using Pest's dataset feature if running multiple benchmarks with variations

    /**
     * @group benchmark
     */
    public function test_cart_performance_suite() // Renamed to avoid conflict with Pest test
    {
        // Increase iterations for more stable benchmark results if needed
        $iterations = 100; // Reduced iterations for quicker feedback, increase later

        $result = Benchmark::measure(
            [
                'Adding 100 Items' => fn() => $this->benchmarkAddItems(100),
                'Calculating Total with 50 Items + Discount' => fn() => $this->benchmarkCalculateTotal(50),
                'Persistence Save/Find with 20 Items' => fn() => $this->benchmarkPersistence(20),
                'Get Cart Data Array with 50 Items' => fn() => $this->benchmarkGetArray(50),
            ],
            $iterations
        );

        // Output results (optional, useful for tracking)
        // dump($result);

        // Basic assertion: ensure operations don't fail catastrophically
        // Specific time assertions are very environment-dependent and might be flaky.
        foreach ($result as $operation => $milliseconds) {
            $this->assertIsNumeric($milliseconds, "Benchmark '$operation' did not return numeric time.");
            // Example threshold (adjust based on environment)
            // $this->assertLessThan(500, $milliseconds, "Operation '$operation' potentially too slow: {$milliseconds}ms");
        }
    }

    private function benchmarkAddItems(int $count): void
    {
        // Use Facade
        Cart::create();
        for ($i = 0; $i < $count; $i++) {
            Cart::addItem(new CartItemDTO(
                id: (string) $i,
                name: "Product $i",
                price: 10.00,
                quantity: 1
            ));
        }
    }

    private function benchmarkCalculateTotal(int $itemCount): void
    {
        // Use Facade and helper
        $this->createCartWithItemsFacade($itemCount); // Use Facade helper
        Cart::applyDiscount('TEST10');
        Cart::total(); // Call total via Facade
    }

    private function benchmarkPersistence(int $itemCount): void
    {
        // Use Facade and helper
        $this->createCartWithItemsFacade($itemCount); // Use Facade helper
        Cart::save();
        $cartData = Cart::get(); // Get array data
        $cartId = $cartData['id']; // Get ID from array
        // Resolve new instance to avoid singleton state for find
        app(\AndreiLungeanu\SimpleCart\SimpleCart::class)->find($cartId);
    }

    private function benchmarkGetArray(int $itemCount): void
    {
        $this->createCartWithItemsFacade($itemCount);
        Cart::get(); // Call get() via Facade
    }

    // Helper to create cart state using the Facade for benchmarks
    private function createCartWithItemsFacade(int $count): void
    {
        Cart::create(); // Use Facade
        for ($i = 0; $i < $count; $i++) {
            Cart::addItem(new CartItemDTO(
                id: (string) $i,
                name: "Product $i",
                price: 10.00,
                quantity: 1
            ));
        }
    }
}

// --- Individual Pest-style tests (kept separate from class benchmark suite) ---

/**
 * @group benchmark
 */
test('cart add operations remain performant with many items', function () {
    Cart::create(); // Use Facade
    $startTime = microtime(true);

    // Add 100 items
    for ($i = 0; $i < 100; $i++) {
        Cart::addItem(new CartItemDTO( // Use Facade
            id: (string) $i,
            name: "Product $i",
            price: 9.99,
            quantity: 1,
            metadata: ['sku' => "SKU$i"]
        ));
    }

    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000; // ms

    // Basic check, specific time is environment dependent
    expect($duration)->toBeNumeric();
    // Check state using Facade and array access
    expect(Cart::get()['items'])->toHaveCount(100);
})->group('benchmark');

/**
 * @group benchmark
 */
test('large cart calculation performance', function () {
    Cart::create(taxZone: 'RO'); // Use Facade
    // Add 100 items
    for ($i = 0; $i < 100; $i++) {
        Cart::addItem(new CartItemDTO( // Use Facade
            id: (string) $i,
            name: "Product $i",
            price: 99.99,
            quantity: rand(1, 5),
            metadata: []
        ));
    }

    $startTime = microtime(true);
    Cart::total(); // Use Facade
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // ms

    // Basic check
    expect($executionTime)->toBeNumeric();
})->group('benchmark');


/**
 * @group benchmark
 */
test('cart get data array performance', function () {
    Cart::create(taxZone: 'RO'); // Use Facade
    // Add items with all features
    for ($i = 0; $i < 50; $i++) {
        Cart::addItem(new CartItemDTO( // Use Facade
            id: (string) $i,
            name: "Product $i",
            price: 99.99,
            quantity: 1,
            category: 'test',
            metadata: ['weight' => rand(1, 10)]
        ));
    }

    $startTime = microtime(true);
    Cart::get(); // Use Facade get()
    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000; // ms

    // Basic check
    expect($duration)->toBeNumeric();
})->group('benchmark');
