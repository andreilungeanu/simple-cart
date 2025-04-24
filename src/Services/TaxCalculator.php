<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\CartInstance; // Use CartInstance
use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
// use AndreiLungeanu\SimpleCart\SimpleCart; // No longer used

class TaxCalculator implements Calculator // Implements updated Calculator contract
{
    public function __construct(
        protected TaxRateProvider $provider // TaxRateProvider needs update
    ) {}

    // Update parameter type hint
    public function calculate(CartInstance $cart): float
    {
        if ($cart->isVatExempt()) { // Assumes CartInstance has isVatExempt()
            return 0.0;
        }

        // Assumes CartInstance has getItems() and public taxZone property or getTaxZone() method
        return round($cart->getItems()->sum(function (\AndreiLungeanu\SimpleCart\DTOs\CartItemDTO $item) use ($cart) {
            // Assumes TaxRateProvider methods are updated to accept CartInstance or just relevant properties
            $rate = $item->category ?
                $this->provider->getRateForCategory($cart->getTaxZone(), $item->category) : // Use getter for taxZone
                $this->provider->getRate($cart); // Pass CartInstance

            // Use default rate if category-specific rate is null
            $effectiveRate = $rate ?? $this->provider->getRate($cart); // Pass CartInstance

            $itemTax = $item->price * $item->quantity * $effectiveRate;

            return $itemTax;
        }), 2);
    }
}
