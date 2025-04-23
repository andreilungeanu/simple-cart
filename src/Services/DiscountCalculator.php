<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\SimpleCart;

class DiscountCalculator implements Calculator
{
    public function calculate(SimpleCart $cart): float
    {
        return $cart->getDiscounts()->sum(function (DiscountDTO $discount) use ($cart) {
            return match ($discount->type) {
                'fixed' => $this->calculateFixed($discount),
                'percentage' => $this->calculatePercentage($discount, $cart),
                'shipping' => $this->calculateShipping($discount, $cart),
                default => 0.0,
            };
        });
    }

    private function calculateFixed(DiscountDTO $discount): float
    {
        return $discount->value;
    }

    private function calculatePercentage(DiscountDTO $discount, SimpleCart $cart): float
    {
        $subtotal = $cart->getSubtotal();
        $fixedDiscounts = $cart->getDiscounts()
            ->filter(fn($d) => $d->type === 'fixed')
            ->sum(fn($d) => $d->value);

        return (($subtotal - $fixedDiscounts) * $discount->value) / 100;
    }

    private function calculateShipping(DiscountDTO $discount, SimpleCart $cart): float
    {
        // TODO: Implement actual shipping discount logic
        return $discount->value;
    }
}
