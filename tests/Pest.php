<?php

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Helper function to create a CartInstance with specific state for unit tests.
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
