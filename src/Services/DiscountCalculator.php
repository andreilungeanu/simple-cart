<?php

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Contracts\Calculator;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\SimpleCart; // Import SimpleCart

class DiscountCalculator implements Calculator
{
    // Change type hint from CartDTO to SimpleCart
    public function calculate(SimpleCart $cart): float
    {
        return $cart->getDiscounts()->sum(function (DiscountDTO $discount) use ($cart) {
            return match ($discount->type) {
                'fixed' => $this->calculateFixed($discount),
                'percentage' => $this->calculatePercentage($discount, $cart), // Pass SimpleCart
                'shipping' => $this->calculateShipping($discount, $cart), // Pass SimpleCart
                default => 0.0,
            };
        });
    }

    private function calculateFixed(DiscountDTO $discount): float
    {
        return $discount->value;
    }

    // Change type hint from CartDTO to SimpleCart
    private function calculatePercentage(DiscountDTO $discount, SimpleCart $cart): float
    {
        // Calculate percentage after fixed discounts
        $subtotal = $cart->getSubtotal(); // Call method on SimpleCart
        $fixedDiscounts = $cart->getDiscounts() // Call method on SimpleCart
            ->filter(fn($d) => $d->type === 'fixed')
            ->sum(fn($d) => $d->value);

        return (($subtotal - $fixedDiscounts) * $discount->value) / 100;
    }

    // Change type hint from CartDTO to SimpleCart
    private function calculateShipping(DiscountDTO $discount, SimpleCart $cart): float
    {
        // Implement shipping discount logic - needs access to shipping cost from SimpleCart
        // Example: return min($discount->value, $cart->getShippingAmount());
        return $discount->value; // Placeholder
    }
}
