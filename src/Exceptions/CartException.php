<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Exceptions;

use Exception;

class CartException extends Exception
{
    public static function cartNotFound(string $cartId): self
    {
        return new self("Cart with ID {$cartId} not found");
    }

    public static function invalidItemData(string $field): self
    {
        return new self("Missing or invalid required field: {$field}");
    }

    public static function invalidPrice(): self
    {
        return new self('Price cannot be negative');
    }

    public static function invalidQuantity(): self
    {
        return new self('Quantity must be at least 1');
    }

    public static function tooManyDiscountCodes(int $maxCodes): self
    {
        return new self("Cannot apply more than {$maxCodes} discount codes");
    }

    public static function invalidShippingMethod(string $method): self
    {
        return new self("Invalid shipping method: {$method}");
    }

    public static function invalidTaxZone(string $zone): self
    {
        return new self("Invalid tax zone: {$zone}");
    }

    public static function cartExpired(string $cartId): self
    {
        return new self("Cart {$cartId} has expired");
    }
}
