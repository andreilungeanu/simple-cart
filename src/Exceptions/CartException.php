<?php

namespace AndreiLungeanu\SimpleCart\Exceptions;

class CartException extends \Exception
{
    public static function cartNotFound(): self
    {
        return new self('Cart not found');
    }

    public static function itemNotFound(string $itemId): self
    {
        return new self("Item {$itemId} not found in cart");
    }

    public static function insufficientStock(string $itemId, int $requested, int $available): self
    {
        return new self("Insufficient stock for item {$itemId}. Requested: {$requested}, Available: {$available}");
    }
}
