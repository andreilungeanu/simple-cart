<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\SimpleCart; // Import SimpleCart

class DefaultShippingProvider implements ShippingRateProvider
{
    // Change type hint from CartDTO to SimpleCart
    public function getRate(SimpleCart $cart, string $method): array
    {
        $settings = config('simple-cart.shipping.settings');
        $cost = 0.0;

        if ($cart->getSubtotal() < $settings['free_shipping_threshold']) {
            $cost = $settings['methods'][$method]['cost'] ?? 0.0;
        }

        $vatRate = $cart->isVatExempt() ? 0.0 : ($settings['methods'][$method]['vat_rate'] ?? null);

        return [
            'amount' => $cost,
            'vat_rate' => $vatRate,
            'vat_included' => false,
        ];
    }

    // Change type hint from CartDTO to SimpleCart
    public function getAvailableMethods(SimpleCart $cart): array
    {
        // The cart instance might not be needed here if just listing all configured methods
        return collect(config('simple-cart.shipping.settings.methods'))
            ->map(fn($method, $key) => [
                'name' => $method['name'],
                'vat_rate' => null,
                'vat_included' => false,
            ])
            ->toArray();
    }
}
