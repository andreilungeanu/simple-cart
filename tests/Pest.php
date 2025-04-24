<?php

use AndreiLungeanu\SimpleCart\Tests\TestCase;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\CartInstance; // Import CartInstance instead of SimpleCart
// Remove unused imports for mocking SimpleCart dependencies
// use AndreiLungeanu\SimpleCart\SimpleCart;
// use AndreiLungeanu\SimpleCart\Repositories\CartRepository;
// use AndreiLungeanu\SimpleCart\Services\CartCalculator;
// use AndreiLungeanu\SimpleCart\Actions\AddItemToCartAction;
use Mockery; // Keep Mockery if other helpers use it, or remove if not

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
 * Helper function to create a CartInstance with specific state
 * for testing providers/calculators in Unit tests.
 */
function createCartInstanceForTesting(
    array $items = [],
    ?string $taxZone = null,
    bool $vatExempt = false,
    ?string $shippingMethod = null,
    ?string $userId = null, // Add other relevant state parameters if needed by tests
    array $discounts = [],
    array $notes = [],
    array $extraCosts = [],
    string $id = '' // Allow setting ID if needed
): CartInstance { // Return CartInstance
    // No need to mock dependencies for CartInstance constructor

    // Create CartInstance directly with the desired state
    return new CartInstance(
        id: $id ?: \Illuminate\Support\Str::uuid()->toString(), // Generate ID if not provided
        userId: $userId,
        taxZone: $taxZone,
        items: $items,
        discounts: $discounts,
        notes: $notes,
        extraCosts: $extraCosts,
        shippingMethod: $shippingMethod,
        vatExempt: $vatExempt
    );
}
