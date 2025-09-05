<?php

use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature', 'Unit', 'Performance');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createTestCart(int $userId = 1): Cart
{
    return Cart::create([
        'id' => Str::ulid()->toString(),
        'user_id' => $userId,
        'tax_zone' => 'US',
        'status' => \AndreiLungeanu\SimpleCart\Enums\CartStatusEnum::Active,
        'expires_at' => now()->addDays(30),
    ]);
}

function createTestCartWithItems(int $userId = 1): Cart
{
    $cart = createTestCart($userId);

    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => 'PROD-1',
        'name' => 'Test Product 1',
        'price' => 29.99,
        'quantity' => 2,
        'category' => 'electronics',
    ]);

    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => 'PROD-2',
        'name' => 'Test Product 2',
        'price' => 15.00,
        'quantity' => 1,
        'category' => 'books',
    ]);

    return $cart->refresh(['items']);
}

function createTestCartWithCustomItems(int $userId = 1, array $itemsData = []): Cart
{
    $cart = createTestCart($userId);

    foreach ($itemsData as $itemData) {
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $itemData['product_id'] ?? 'PROD-'.uniqid(),
            'name' => $itemData['name'] ?? 'Test Product',
            'price' => $itemData['price'] ?? 10.00,
            'quantity' => $itemData['quantity'] ?? 1,
            'category' => $itemData['category'] ?? 'general',
            'metadata' => $itemData['metadata'] ?? [],
        ]);
    }

    return $cart->refresh(['items']);
}

function createExpiredCart(int $userId = 1): Cart
{
    return Cart::create([
        'id' => Str::ulid()->toString(),
        'user_id' => $userId,
        'tax_zone' => 'US',
        'status' => \AndreiLungeanu\SimpleCart\Enums\CartStatusEnum::Expired,
        'expires_at' => now()->subDay(),
    ]);
}

function createAbandonedCart(int $userId = 1): Cart
{
    return Cart::create([
        'id' => Str::ulid()->toString(),
        'user_id' => $userId,
        'tax_zone' => 'US',
        'status' => \AndreiLungeanu\SimpleCart\Enums\CartStatusEnum::Abandoned,
        'expires_at' => now()->addDays(30),
    ]);
}

function createCartWithDiscounts(int $userId = 1, array $discounts = []): Cart
{
    $cart = createTestCart($userId);

    if (! empty($discounts)) {
        $discountData = [];
        foreach ($discounts as $discount) {
            if (is_string($discount)) {
                // Convert old string format to new data format
                $discountData[$discount] = createTestDiscountData($discount);
            } else {
                // Already in new format
                $discountData[$discount['code']] = $discount;
            }
        }
        $cart->update(['discount_data' => $discountData]);
    }

    return $cart->refresh();
}

function createTestDiscountData(string $code): array
{
    $defaultDiscounts = createDiscountConfig();

    return $defaultDiscounts[$code] ?? [
        'code' => $code,
        'type' => 'fixed',
        'value' => 10.0,
        'conditions' => ['minimum_amount' => 50.0],
    ];
}

function createDiscountConfig(array $discounts = []): array
{
    $defaultDiscounts = [
        'SAVE10' => [
            'code' => 'SAVE10',
            'type' => 'fixed',
            'value' => 10.0,
            'conditions' => ['minimum_amount' => 50.0],
        ],
        'SAVE20' => [
            'code' => 'SAVE20',
            'type' => 'fixed',
            'value' => 20.0,
            'conditions' => ['minimum_amount' => 100.0],
        ],
        'PERCENT15' => [
            'code' => 'PERCENT15',
            'type' => 'percentage',
            'value' => 15.0,
            'conditions' => ['minimum_amount' => 75.0],
        ],
        'FREESHIP' => [
            'code' => 'FREESHIP',
            'type' => 'free_shipping',
            'value' => 0.0,
            'conditions' => [],
        ],
        'BOOKS20' => [
            'code' => 'BOOKS20',
            'type' => 'percentage',
            'value' => 20.0,
            'conditions' => [
                'category' => 'books',
                'minimum_amount' => 30.0,
            ],
        ],
        'LAPTOP_BULK' => [
            'code' => 'LAPTOP_BULK',
            'type' => 'fixed',
            'value' => 50.0,
            'conditions' => [
                'item_id' => 'laptop_pro',
                'min_quantity' => 2,
            ],
        ],
    ];

    return array_merge($defaultDiscounts, $discounts);
}
