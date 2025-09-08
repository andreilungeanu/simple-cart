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

    public function calculate(Cart $cart, ?array $appliedDiscounts = null): float
    {
        $shippingData = $cart->shipping_data;

        if (! $shippingData || ! isset($shippingData['cost'])) {
            return 0.0;
        }

        // Free shipping discounts take precedence
        $discounts = $appliedDiscounts ?? $cart->discount_data ?? [];
        foreach ($discounts as $discount) {
            if (($discount['type'] ?? '') === 'free_shipping') {
                return 0.0;
            }
        }

        // Then threshold-based free shipping
        if ($this->config->freeShippingThreshold !== null) {
            $subtotal = $cart->subtotal;
            if ($subtotal >= $this->config->freeShippingThreshold) {
                return 0.0;
            }
        }

        return (float) $shippingData['cost'];
    }

    public function isFreeShippingApplied(Cart $cart, ?array $appliedDiscounts = null): bool
    {
        $shippingData = $cart->shipping_data;

        if ($shippingData === null || ! isset($shippingData['cost'])) {
            return false;
        }

        // Free shipping discounts take precedence
        $discounts = $appliedDiscounts ?? $cart->discount_data ?? [];
        foreach ($discounts as $discount) {
            if (($discount['type'] ?? '') === 'free_shipping') {
                return true;
            }
        }

        // Then threshold-based free shipping
        return $this->config->freeShippingThreshold !== null &&
               $cart->subtotal >= $this->config->freeShippingThreshold;
    }

    public function getAppliedShipping(Cart $cart): ?array
    {
        return $cart->shipping_data;
    }
}
