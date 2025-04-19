<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

class TaxCalculator implements Calculator
{
    public function __construct(
        protected TaxRateProvider $provider
    ) {}

    public function calculate(CartDTO $cart): float
    {
        if ($cart->isVatExempt()) {
            return 0.0;
        }

        return round($cart->getItems()->sum(function ($item) use ($cart) {
            $rate = $item->category ?
                $this->provider->getRateForCategory($cart->taxZone, $item->category) :
                $this->provider->getRate($cart);

            $itemTax = $item->price * $item->quantity * ($rate ?? $this->provider->getRate($cart));

            return $itemTax;
        }), 2);
    }
}
