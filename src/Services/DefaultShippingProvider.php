<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingRateProvider;

class DefaultShippingProvider implements ShippingRateProvider
{
    public function getRate(CartInstance $cart, string $method): array
    {
        $settings = config('simple-cart.shipping.settings', []);
        $methods = $settings['methods'] ?? [];
        $methodSettings = $methods[$method] ?? [];
        $cost = $methodSettings['cost'] ?? 0.0;

        $vatRate = $cart->isVatExempt()
            ? 0.0
            : ($methodSettings['vat_rate'] ?? null);

        return [
            'amount' => $cost,
            'vat_rate' => $vatRate,
            'vat_included' => false, // Default assumes VAT is not included in the base cost
        ];
    }

    public function getAvailableMethods(CartInstance $cart): array
    {
        return collect(config('simple-cart.shipping.settings.methods', []))
            ->map(fn($methodConfig, $key) => [
                'name' => $methodConfig['name'] ?? 'Unknown Method',
                // These are informational and might not reflect actual calculation logic
                'vat_rate' => null,
                'vat_included' => false,
            ])
            ->toArray();
    }
}
