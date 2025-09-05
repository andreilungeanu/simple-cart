<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Services\Calculators;

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Models\Cart;

class ShippingCalculator
{
    public function __construct(
        private CartConfiguration $config
    ) {}

    public function calculate(Cart $cart): float
    {
        if (! $cart->shipping_method) {
            return 0.0;
        }

        $subtotal = $cart->subtotal;

        // Free shipping threshold
        if ($subtotal >= $this->config->freeShippingThreshold) {
            return 0.0;
        }

        $method = $this->config->getShippingMethod($cart->shipping_method);
        if (! $method) {
            return 0.0;
        }

        return match ($method['type'] ?? 'flat') {
            'weight' => $this->calculateWeightBased($cart, $method),
            'percentage' => $subtotal * ($method['rate'] ?? 0),
            'flat' => $method['cost'] ?? 0.0,
            default => 0.0,
        };
    }

    public function isFreeShippingApplied(Cart $cart): bool
    {
        return $cart->shipping_method !== null &&
               $cart->subtotal >= $this->config->freeShippingThreshold;
    }

    public function getAvailableMethods(Cart $cart): array
    {
        $methods = $this->config->getShippingMethods();

        // Filter methods based on cart criteria (weight, destination, etc.)
        return array_filter($methods, function ($method, $key) {
            // Add filtering logic here if needed
            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function calculateWeightBased(Cart $cart, array $method): float
    {
        $totalWeight = $cart->items->sum(function ($item) {
            return ($item->metadata['weight'] ?? 0) * $item->quantity;
        });

        return $totalWeight * ($method['rate_per_kg'] ?? 0);
    }
}
