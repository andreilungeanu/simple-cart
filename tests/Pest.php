<?php

use AndreiLungeanu\SimpleCart\Tests\TestCase;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\CartInstance;

uses(TestCase::class)->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Global Test Helper Functions
|--------------------------------------------------------------------------
|
| Define helper functions here that can be used across your test suite.
|
*/

/**
 * Helper function to create a CartInstance with specific state
 * for testing providers/calculators in Unit tests.
 */
function createCartInstanceForTesting(
    array $items = [],
    ?string $taxZone = null,
    bool $vatExempt = false,
    ?string $shippingMethod = null,
    ?string $userId = null,
    array $discounts = [],
    array $notes = [],
    array $extraCosts = [],
    string $id = ''
): CartInstance {
    return new CartInstance(
        id: $id ?: \Illuminate\Support\Str::uuid()->toString(),
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
