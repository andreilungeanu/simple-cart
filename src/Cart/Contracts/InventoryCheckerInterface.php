<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

interface InventoryCheckerInterface
{
    /**
     * Return available quantity for a SKU.
     */
    public function availableQuantity(string $sku): int;
}
