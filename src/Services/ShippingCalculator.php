<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\CartInstance; // Use CartInstance
use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO; // Needed for subtotal calculation
// use AndreiLungeanu\SimpleCart\SimpleCart; // No longer used

class ShippingCalculator implements Calculator // Calculator contract might need update
{
    public function __construct(
        protected ShippingRateProvider $provider // ShippingRateProvider needs update
    ) {}

    // Update parameter type hint
    public function calculate(CartInstance $cart): float
    {
        $shippingMethod = $cart->getShippingMethod(); // Assumes CartInstance has getShippingMethod()
        if (! $shippingMethod) {
            return 0.0;
        }

        // Calculate subtotal here to check threshold
        $subtotal = $cart->getItems()->sum(
            fn(CartItemDTO $item) => $item->price * $item->quantity
        );

        // Check free shipping threshold from config
        $threshold = config('simple-cart.shipping.settings.free_shipping_threshold', null);
        if ($threshold !== null && $subtotal >= $threshold) {
            return 0.0; // Free shipping applies
        }

        // If not free, get rate from provider
        // Assumes ShippingRateProvider::getRate is updated to accept CartInstance
        $info = $this->provider->getRate($cart, $shippingMethod);

        // Return amount, rounded for consistency
        return round($info['amount'] ?? 0.0, 2);
    }

    // Update parameter type hint
    public function getShippingInfo(CartInstance $cart): ?array
    {
        if (! $cart->getShippingMethod()) { // Assumes CartInstance has getShippingMethod()
            return null;
        }

        // Assumes ShippingRateProvider::getRate is updated to accept CartInstance
        $info = $this->provider->getRate($cart, $cart->getShippingMethod());

        if ($info['vat_rate'] !== null && ($info['vat_rate'] < 0 || $info['vat_rate'] > 1)) {
            throw new \InvalidArgumentException('VAT rate must be between 0 and 1');
        }

        if ($cart->isVatExempt()) { // Assumes CartInstance has isVatExempt()
            $info['vat_rate'] = 0.0;
            $info['vat_included'] = false;
        }

        return $info;
    }
}
