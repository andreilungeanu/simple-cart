<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

class ShippingCalculator implements Calculator
{
    public function __construct(
        protected ShippingRateProvider $provider
    ) {}

    public function calculate(CartDTO $cart): float
    {
        if (!$cart->getShippingMethod()) {
            return 0.0;
        }

        if ($this->isEligibleForFreeShipping($cart)) {
            return 0.0;
        }

        $rateInfo = $this->provider->getRate($cart, $cart->getShippingMethod());
        return round($rateInfo['amount'], 2);
    }

    protected function isEligibleForFreeShipping(CartDTO $cart): bool
    {
        $threshold = (float)config('simple-cart.shipping.settings.free_shipping_threshold');


        return $cart->getSubtotal() >= $threshold;
    }

    public function getAvailableMethods(CartDTO $cart): array
    {
        return $this->provider->getAvailableMethods($cart);
    }

    public function getShippingInfo(CartDTO $cart): ?array
    {
        if (!$cart->getShippingMethod()) {
            return null;
        }

        $info = $this->provider->getRate($cart, $cart->getShippingMethod());

        if ($cart->isVatExempt()) {
            $info['vat_rate'] = 0.0;
            $info['vat_included'] = false;
        }

        return $info;
    }
}
