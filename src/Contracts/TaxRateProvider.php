<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance; // Use CartInstance

/**
 * Interface for services that provide tax rates.
 */
interface TaxRateProvider
{
    /**
     * Get the default tax rate applicable to the cart instance (e.g., based on its tax zone).
     *
     * @param CartInstance $cart The cart instance.
     * @return float The tax rate (e.g., 0.19 for 19%).
     */
    public function getRate(CartInstance $cart): float;

    /**
     * Get a specific tax rate for a given zone and item category.
     * Returns null if no specific rate applies.
     *
     * @param string|null $zone The tax zone identifier (can be null).
     * @param string $category The item category identifier.
     * @return float|null The specific tax rate or null.
     */
    public function getRateForCategory(?string $zone, string $category): ?float; // Made zone nullable

    public function getAvailableZones(): array;
}
