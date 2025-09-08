<?php

use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;

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
| Factory Usage Examples
|--------------------------------------------------------------------------
|
| Below are some examples of how to use the new factories in your tests.
| These replace the old helper functions for cleaner, more flexible testing.
|
| Basic Cart:
|   Cart::factory()->create()
|
| Cart with items:
|   Cart::factory()->has(CartItem::factory()->count(3))->create()
|
| Specific user cart:
|   Cart::factory()->forUser(1)->create()
|
| Expired cart:
|   Cart::factory()->expired()->create()
|
| Cart with discounts:
|   Cart::factory()->withDiscounts(['SAVE10', 'PERCENT15'])->create()
|
| Electronics items:
|   CartItem::factory()->electronics()->count(5)->create()
|
| Cart with specific items:
|   Cart::factory()
|       ->has(CartItem::factory()->testProduct1())
|       ->has(CartItem::factory()->testProduct2())
|       ->create()
|
*/
