<?php

namespace AndreiLungeanu\SimpleCart\Cart\Services\Calculation;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\Cart\Contracts\DiscountCalculatorInterface;

class DiscountCalculator implements DiscountCalculatorInterface
{
    /**
     * Calculate the total discount amount for the cart.
     *
     * @param CartInstance $cart The cart instance.
     * @param float $subtotal The pre-calculated subtotal (items total before discounts).
     * @return float The total discount amount.
     */
    public function calculate(CartInstance $cart, float $subtotal): float
    {
        $fixedDiscountTotal = $cart->getDiscounts()
            ->where('type', 'fixed')
            ->sum(fn(DiscountDTO $d) => $this->calculateFixed($d));

        $percentageDiscountTotal = $cart->getDiscounts()
            ->where('type', 'percentage')
            ->sum(fn(DiscountDTO $d) => $this->calculatePercentage($d, $subtotal - $fixedDiscountTotal));

        $shippingDiscountTotal = $cart->getDiscounts()
            ->where('type', 'shipping')
            ->sum(fn(DiscountDTO $d) => $this->calculateShipping($d, $cart));

        return round($fixedDiscountTotal + $percentageDiscountTotal + $shippingDiscountTotal, 2);
    }

    private function calculateFixed(DiscountDTO $discount): float
    {
        return $discount->value;
    }

    /**
     * Calculate percentage discount based on a given base amount.
     *
     * @param DiscountDTO $discount
     * @param float $baseAmount (e.g., subtotal after fixed discounts)
     * @return float
     */
    private function calculatePercentage(DiscountDTO $discount, float $baseAmount): float
    {
        $effectiveBase = max(0, $baseAmount);
        return ($effectiveBase * $discount->value) / 100;
    }

    /**
     * Calculate shipping discount.
     *
     * @param DiscountDTO $discount
     * @param CartInstance $cart
     * @return float
     */
    private function calculateShipping(DiscountDTO $discount, CartInstance $cart): float
    {
        // TODO: Implement actual shipping discount logic.
        return $discount->value;
    }
}
