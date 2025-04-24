<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;

class DefaultTaxProvider implements TaxRateProvider
{
    public function getRate(CartInstance $cart): float
    {
        $taxZone = $cart->getTaxZone();
        if (! $taxZone) {
            return 0.0;
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

    public function getRateForCategory(?string $zone, string $category): ?float
    {
        if (! $zone) {
            return null;
        }
        $zoneConfig = config("simple-cart.tax.settings.zones.{$zone}");

        return $zoneConfig['rates_by_category'][$category] ?? null;
    }
}
