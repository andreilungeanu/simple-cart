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
        $cart = Cart::create();
        $cartId = $cart->getId();
        for ($i = 0; $i < $count; $i++) {
            Cart::addItem($cartId, new CartItemDTO( // Pass cartId
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
        $cartId = $this->createCartWithItemsFacade($itemCount); // Use Facade helper, get cartId
        Cart::applyDiscount($cartId, new \AndreiLungeanu\SimpleCart\DTOs\DiscountDTO(code: 'TEST10', type: 'fixed', value: 1.0)); // Pass cartId and DTO
        Cart::total($cartId); // Call total via Facade, pass cartId
    }

    private function benchmarkPersistence(int $itemCount): void
    {
        // Use Facade and helper
        $cartId = $this->createCartWithItemsFacade($itemCount); // Use Facade helper, get cartId
        // Save is implicit in addItem, no explicit save needed via manager
        // Find the cart instance
        Cart::find($cartId); // Use find via Facade
    }

    // This benchmark is less relevant now as get() is removed.
    // We can benchmark finding and accessing properties instead.
    private function benchmarkGetArray(int $itemCount): void
    {
        $cartId = $this->createCartWithItemsFacade($itemCount);
        $cart = Cart::find($cartId); // Find the instance
        // Access some properties or call getters if needed
        $items = $cart->getItems();
        $id = $cart->getId();
    }

    // Helper to create cart state using the Facade for benchmarks
    // Returns the cart ID
    private function createCartWithItemsFacade(int $count): string
    {
        $cart = Cart::create(); // Use Facade
        $cartId = $cart->getId();
        for ($i = 0; $i < $count; $i++) {
            Cart::addItem($cartId, new CartItemDTO( // Pass cartId
                id: (string) $i,
                name: "Product $i",
                price: 10.00,
                quantity: 1
            ));
        }
        return $cartId; // Return the ID
    }
}

// --- Individual Pest-style tests (kept separate from class benchmark suite) ---

/**
 * @group benchmark
 */
test('cart add operations remain performant with many items', function () {
    $cartWrapper = Cart::create(); // Use Facade, returns wrapper
    $cartId = $cartWrapper->getId();
    $startTime = microtime(true);

    // Add 100 items
    for ($i = 0; $i < 100; $i++) {
        $cartWrapper->addItem(new CartItemDTO( // Use wrapper
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
    // Check state using find and getInstance
    $loadedCartWrapper = Cart::find($cartId);
    expect($loadedCartWrapper->getInstance()->getItems())->toHaveCount(100);
})->group('benchmark');

/**
 * @group benchmark
 */
test('large cart calculation performance', function () {
    $cartWrapper = Cart::create(taxZone: 'RO'); // Use Facade, returns wrapper
    $cartId = $cartWrapper->getId();
    // Add 100 items
    for ($i = 0; $i < 100; $i++) {
        $cartWrapper->addItem(new CartItemDTO( // Use wrapper
            id: (string) $i,
            name: "Product $i",
            price: 99.99,
            quantity: rand(1, 5),
            metadata: []
        ));
    }

    $startTime = microtime(true);
    Cart::total($cartId); // Use Facade, pass cartId
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // ms

    // Basic check
    expect($executionTime)->toBeNumeric();
})->group('benchmark');


/**
 * @group benchmark
 */
// Renamed test as get() is removed
test('cart find and access performance', function () {
    $cartWrapper = Cart::create(taxZone: 'RO'); // Use Facade, returns wrapper
    $cartId = $cartWrapper->getId();
    // Add items with all features
    for ($i = 0; $i < 50; $i++) {
        $cartWrapper->addItem(new CartItemDTO( // Use wrapper
            id: (string) $i,
            name: "Product $i",
            price: 99.99,
            quantity: 1,
            category: 'test',
            metadata: ['weight' => rand(1, 10)]
        ));
    }

    $startTime = microtime(true);
    $loadedCartWrapper = Cart::find($cartId); // Use find via Facade, returns wrapper
    $items = $loadedCartWrapper->getInstance()->getItems(); // Get instance then items
    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000; // ms

    // Basic check
    expect($duration)->toBeNumeric();
})->group('benchmark');
