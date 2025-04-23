<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\SimpleCart;

class ShippingCalculator implements Calculator
{
    public function __construct(
        protected ShippingRateProvider $provider
    ) {}

    public function calculate(SimpleCart $cart): float
    {
        if (! $cart->getShippingMethod()) {
            return 0.0;
        }

        $info = $this->provider->getRate($cart, $cart->getShippingMethod());

        return $info['amount'];
    }

    public function getShippingInfo(SimpleCart $cart): ?array
    {
        if (! $cart->getShippingMethod()) {
            return null;
        }

        $info = $this->provider->getRate($cart, $cart->getShippingMethod());

        if ($info['vat_rate'] !== null && ($info['vat_rate'] < 0 || $info['vat_rate'] > 1)) {
            throw new \InvalidArgumentException('VAT rate must be between 0 and 1');
        }

        if ($cart->isVatExempt()) {
            $info['vat_rate'] = 0.0;
            $info['vat_included'] = false;
        }

        return $info;
    }
}
