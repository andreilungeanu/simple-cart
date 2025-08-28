<?php

namespace AndreiLungeanu\SimpleCart\Cart\Services\Calculation;

use AndreiLungeanu\SimpleCart\Cart\Contracts\DiscountCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\CartInstance;

class DiscountCalculator implements DiscountCalculatorInterface
{
    /**
     * Calculate the total discount amount for the cart.
     *
     * @param  CartInstance  $cart  The cart instance.
     * @param  float  $subtotal  The pre-calculated subtotal (items total before discounts).
     * @return float The total discount amount.
     */
    public function calculate(CartInstance $cart, float $subtotal, float $shippingAmount = 0.0): float
    {
        $fixedDiscountTotal = $cart->getDiscounts()
            ->where('type', 'fixed')
            ->sum(fn (DiscountDTO $d) => $this->calculateFixed($d));

        $percentageDiscountTotal = $cart->getDiscounts()
            ->where('type', 'percentage')
            ->sum(fn (DiscountDTO $d) => $this->calculatePercentage($d, $subtotal - $fixedDiscountTotal));

        // Calculate shipping discounts but cap the aggregate shipping discount
        // to the total shipping amount (so discounts can make shipping free but not negative).
        $shippingDiscountTotal = $cart->getDiscounts()
            ->where('type', 'shipping')
            ->sum(fn (DiscountDTO $d) => $this->calculateShipping($d, $shippingAmount));

        $shippingDiscountTotal = min($shippingDiscountTotal, max(0.0, $shippingAmount));

        return round($fixedDiscountTotal + $percentageDiscountTotal + $shippingDiscountTotal, 2);
    }

    private function calculateFixed(DiscountDTO $discount): float
    {
        return $discount->value;
    }

    /**
     * Calculate percentage discount based on a given base amount.
     *
     * @param  float  $baseAmount  (e.g., subtotal after fixed discounts)
     */
    private function calculatePercentage(DiscountDTO $discount, float $baseAmount): float
    {
        $effectiveBase = max(0, $baseAmount);

        return ($effectiveBase * $discount->value) / 100;
    }

    /**
     * Calculate shipping discount.
     */
    private function calculateShipping(DiscountDTO $discount, float $shippingAmount): float
    {
        if ($shippingAmount <= 0) {
            return 0.0;
        }

        if ($discount->value <= 0) {
            return 0.0;
        }

        // Interpret shipping discount value as fixed amount when type 'shipping' is used.
        // We also support percentage mode via the 'appliesTo' field if set to 'percentage'.
        if ($discount->appliesTo === 'percentage') {
            return ($shippingAmount * $discount->value) / 100;
        }

        // Default: fixed amount
        return min($discount->value, $shippingAmount);
    }
}
