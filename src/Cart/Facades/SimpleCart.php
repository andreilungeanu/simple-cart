<?php

namespace AndreiLungeanu\SimpleCart\Cart\Facades;

use Illuminate\Support\Facades\Facade;
use AndreiLungeanu\SimpleCart\Cart\Contracts\CartManagerInterface;

/**
 * @see \AndreiLungeanu\SimpleCart\SimpleCart
 * @method static \AndreiLungeanu\SimpleCart\FluentCart find(string $cartId)
 * @method static \AndreiLungeanu\SimpleCart\FluentCart findOrFail(string $cartId)
 * @method static \AndreiLungeanu\SimpleCart\FluentCart create(?string $cartId = null, ?string $userId = null, ?string $taxZone = null)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance addItem(string $cartId, \AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO|array $item)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance removeItem(string $cartId, string $itemId)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance updateQuantity(string $cartId, string $itemId, int $quantity)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance clear(string $cartId)
 * @method static bool destroy(string $cartId)
 * @method static float total(string $cartId)
 * @method static float subtotal(string $cartId)
 * @method static float taxAmount(string $cartId)
 * @method static float shippingAmount(string $cartId)
 * @method static float discountAmount(string $cartId)
 * @method static float extraCostsTotal(string $cartId)
 * @method static int itemCount(string $cartId)
 * @method static bool isFreeShippingApplied(string $cartId)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance applyDiscount(string $cartId, \AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO|array $discount)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance removeDiscount(string $cartId, string $code)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance addNote(string $cartId, string $note)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance removeExtraCost(string $cartId, string $name)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance addExtraCost(string $cartId, \AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO|array $cost)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance setShippingMethod(string $cartId, string $method, array $shippingInfo)
 * @method static \AndreiLungeanu\SimpleCart\CartInstance setVatExempt(string $cartId, bool $exempt = true)
 */
class SimpleCart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CartManagerInterface::class;
    }
}
