<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;

class DiscountCalculator implements Calculator
{
    public function calculate(CartDTO $cart): float
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

    private function calculatePercentage(DiscountDTO $discount, CartDTO $cart): float
    {
        // Calculate percentage after fixed discounts
        $subtotal = $cart->getSubtotal();
        $fixedDiscounts = $cart->getDiscounts()
            ->filter(fn($d) => $d->type === 'fixed')
            ->sum(fn($d) => $d->value);

        return (($subtotal - $fixedDiscounts) * $discount->value) / 100;
    }

    private function calculateShipping(DiscountDTO $discount, CartDTO $cart): float
    {
        // Implement shipping discount logic
        return $discount->value;
    }
}
