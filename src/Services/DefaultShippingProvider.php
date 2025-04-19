<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

class DefaultShippingProvider implements ShippingRateProvider
{
    public function getRate(CartDTO $cart, string $method): array
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

    public function getAvailableMethods(CartDTO $cart): array
    {
        return collect(config('simple-cart.shipping.settings.methods'))
            ->map(fn($method, $key) => [
                'name' => $method['name'],
                'vat_rate' => null,
                'vat_included' => false,
            ])
            ->toArray();
    }
}
