<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;

class TaxCalculator implements Calculator
{
    public function __construct(
        protected TaxRateProvider $provider
    ) {}

    public function calculate(CartInstance $cart): float
    {
        if ($cart->isVatExempt()) {
            return 0.0;
        }

        return round($cart->getItems()->sum(function (\AndreiLungeanu\SimpleCart\DTOs\CartItemDTO $item) use ($cart) {
            $rate = $item->category ?
                $this->provider->getRateForCategory($cart->getTaxZone(), $item->category) :
                $this->provider->getRate($cart);

            $effectiveRate = $rate ?? $this->provider->getRate($cart);

            $itemTax = $item->price * $item->quantity * $effectiveRate;

            return $itemTax;
        }), 2);
    }
}
