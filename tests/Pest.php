<?php

use AndreiLungeanu\SimpleCart\Tests\TestCase;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO; // Import the DTO
use AndreiLungeanu\SimpleCart\SimpleCart; // Import SimpleCart
use AndreiLungeanu\SimpleCart\Repositories\CartRepository; // For mocking helper
use AndreiLungeanu\SimpleCart\Services\CartCalculator; // For mocking helper
use AndreiLungeanu\SimpleCart\Actions\AddItemToCartAction; // For mocking helper
// use Mockery; // For mocking helper - Not needed globally

uses(TestCase::class)->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Global Test Helper Functions
|--------------------------------------------------------------------------
|
| Define helper functions here that can be used across your test suite.
|
*/

// Removed createTestItem helper function as requested.

/**
 * Helper function to create a SimpleCart instance with mocked dependencies
 * for testing providers/calculators in Unit tests.
 */
function createTestCartInstance(
    array $items = [],
    ?string $taxZone = null,
    bool $vatExempt = false,
    ?string $shippingMethod = null
): SimpleCart {
    // Mock dependencies needed for SimpleCart constructor
    $mockRepo = Mockery::mock(CartRepository::class);
    $mockCalculator = Mockery::mock(CartCalculator::class);
    // Mock getSubtotal as DefaultShippingProvider uses it
    $subtotal = collect($items)->sum(fn($item) => $item->price * $item->quantity);
    // Allow getSubtotal mock to be called multiple times if needed within a test
    $mockCalculator->shouldReceive('getSubtotal')->zeroOrMoreTimes()->andReturn($subtotal);

    $mockAction = Mockery::mock(AddItemToCartAction::class);

    // Create SimpleCart instance with mocks and initial state
    return new SimpleCart(
        repository: $mockRepo,
        calculator: $mockCalculator,
        addItemAction: $mockAction,
        items: $items,
        taxZone: $taxZone,
        vatExempt: $vatExempt,
        shippingMethod: $shippingMethod
    );
}
