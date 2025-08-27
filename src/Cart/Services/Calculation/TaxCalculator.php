<?php

namespace AndreiLungeanu\SimpleCart\Cart\Services\Calculation;

use AndreiLungeanu\SimpleCart\Cart\Contracts\TaxCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\CartInstance;

class TaxCalculator implements TaxCalculatorInterface
{
    public function __construct(
        protected TaxRateProvider $provider
    ) {}

    public function calculate(CartInstance $cart): float
    {
        if ($cart->isVatExempt()) {
            return 0.0;
        }

        return round($cart->getItems()->sum(function (\AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO $item) use ($cart) {
            $rate = $item->category ?
                $this->provider->getRateForCategory($cart->getTaxZone(), $item->category) :
                $this->provider->getRate($cart);

            $effectiveRate = $rate ?? $this->provider->getRate($cart);

            $itemTax = $item->price * $item->quantity * $effectiveRate;

            return $itemTax;
        }), 2);
    }
}
