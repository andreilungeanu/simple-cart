<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\CartInstance; // Use CartInstance
use AndreiLungeanu\SimpleCart\Contracts\ShippingRateProvider;
// use AndreiLungeanu\SimpleCart\SimpleCart; // No longer used

class DefaultShippingProvider implements ShippingRateProvider
{
    // Update parameter type hint
    public function getRate(CartInstance $cart, string $method): array
    {
        $settings = config('simple-cart.shipping.settings', []); // Add default empty array
        $methods = $settings['methods'] ?? [];
        $methodSettings = $methods[$method] ?? [];
        $cost = $methodSettings['cost'] ?? 0.0;

        // TODO: Free shipping threshold logic ideally belongs in ShippingCalculator, not the provider.
        // The provider should just return the base rate for the method.
        // For now, keep the logic but note it needs CartCalculator injected or logic moved.
        // This will break as CartInstance doesn't have getSubtotal().
        // Temporary fix: Assume cost is always applied for now. Refactor later.
        // if ($cart->getSubtotal() < ($settings['free_shipping_threshold'] ?? PHP_FLOAT_MAX)) {
        //     $cost = $methodSettings['cost'] ?? 0.0;
        // }

        $vatRate = $cart->isVatExempt() // Assumes CartInstance has isVatExempt()
            ? 0.0
            : ($methodSettings['vat_rate'] ?? null);

        return [
            'amount' => $cost,
            'vat_rate' => $vatRate,
            'vat_included' => false,
        ];
    }

    // Update parameter type hint
    public function getAvailableMethods(CartInstance $cart): array
    {
        // The available methods likely don't depend on the specific cart instance state in this default provider
        // but the interface requires the parameter, so we keep it.
        return collect(config('simple-cart.shipping.settings.methods', [])) // Add default
            ->map(fn($methodConfig, $key) => [
                'name' => $methodConfig['name'] ?? 'Unknown Method', // Add default
                'vat_rate' => null,
                'vat_included' => false,
            ])
            ->toArray();
    }
}
