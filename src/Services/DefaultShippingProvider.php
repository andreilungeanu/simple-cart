<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Cart\Contracts\ShippingRateProviderInterface;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingMethodDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingRateDTO;
use AndreiLungeanu\SimpleCart\CartInstance;

class DefaultShippingProvider implements ShippingRateProviderInterface
{
    public function getRate(CartInstance $cart, string $method): ?ShippingRateDTO
    {
        $settings = config('simple-cart.shipping.settings', []);
        $methods = $settings['methods'] ?? [];
        $methodSettings = $methods[$method] ?? [];
        $cost = $methodSettings['cost'] ?? 0.0;

        $vatRate = $cart->isVatExempt()
            ? 0.0
            : ($methodSettings['vat_rate'] ?? null);

        return new ShippingRateDTO(
            amount: (float) $cost,
            vatRate: $vatRate,
            vatIncluded: false,
        );
    }

    public function getAvailableMethods(CartInstance $cart): array
    {
        return collect(config('simple-cart.shipping.settings.methods', []))
            ->map(fn ($methodConfig, $key) => new ShippingMethodDTO(
                id: (string) $key,
                name: $methodConfig['name'] ?? 'Unknown Method',
                description: $methodConfig['description'] ?? null,
            ))
            ->values()
            ->toArray();
    }
}
