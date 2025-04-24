<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\CartInstance; // Use CartInstance
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
// use AndreiLungeanu\SimpleCart\SimpleCart; // No longer used

class DefaultTaxProvider implements TaxRateProvider
{
    // Update parameter type hint
    public function getRate(CartInstance $cart): float
    {
        $taxZone = $cart->getTaxZone(); // Use getter
        if (! $taxZone) {
            return 0.0; // No zone, no default rate
        }

        return $this->getRateForZone($taxZone);
    }

    public function getAvailableZones(): array
    {
        return config('simple-cart.tax.settings.zones');
    }

    protected function getRateForZone(string $zone): float
    {
        $zoneConfig = $this->getAvailableZones()[$zone] ?? null;

        return $zoneConfig['default_rate'] ?? 0.0;
    }

    // Update zone parameter type hint to match contract
    public function getRateForCategory(?string $zone, string $category): ?float
    {
        if (! $zone) {
            return null; // No zone specified, cannot get category rate for a specific zone
        }
        // Use null-safe access for config
        $zoneConfig = config("simple-cart.tax.settings.zones.{$zone}");

        return $zoneConfig['rates_by_category'][$category] ?? null;
    }

    // Removed unused/misplaced methods: shouldApplyToShipping, calculate
}
