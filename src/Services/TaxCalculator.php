<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\SimpleCart; // Import SimpleCart

class TaxCalculator implements Calculator
{
    public function __construct(
        protected TaxRateProvider $provider
    ) {}

    // Change type hint from CartDTO to SimpleCart
    public function calculate(SimpleCart $cart): float
    {
        if ($cart->isVatExempt()) {
            return 0.0;
        }

        // Ensure $item is type-hinted if possible, assuming it's CartItemDTO from getItems()
        return round($cart->getItems()->sum(function (\AndreiLungeanu\SimpleCart\DTOs\CartItemDTO $item) use ($cart) {
            $rate = $item->category ?
                $this->provider->getRateForCategory($cart->taxZone, $item->category) :
                $this->provider->getRate($cart); // Pass SimpleCart instance

            // Pass SimpleCart instance
            $itemTax = $item->price * $item->quantity * ($rate ?? $this->provider->getRate($cart));

            return $itemTax;
        }), 2);
    }
}
