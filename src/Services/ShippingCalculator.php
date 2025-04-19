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

        $rateInfo = $this->provider->getRate($cart, $cart->getShippingMethod());
        return $rateInfo['amount'];
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
