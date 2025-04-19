<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

class DefaultTaxProvider implements TaxRateProvider
{
    public function getRate(CartDTO $cart): float
    {
        if (!$cart->taxZone) {
            return 0.0;
        }

        return $this->getRateForZone($cart->taxZone);
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

    public function getRateForCategory(string $zone, string $category): ?float
    {
        $zoneConfig = config("simple-cart.tax.settings.zones.{$zone}");
        return $zoneConfig['rates_by_category'][$category] ?? null;
    }

    protected function shouldApplyToShipping(string $zone): bool
    {
        $zoneConfig = $this->getAvailableZones()[$zone] ?? null;
        return $zoneConfig['apply_to_shipping'] ?? false;
    }

    protected function calculate(CartDTO $cart, float $rate): float
    {
        return $cart->getSubtotal() * $rate;
    }
}
