<?php

namespace AndreiLungeanu\SimpleCart\Services;

// No longer implements Calculator directly due to signature change
use AndreiLungeanu\SimpleCart\CartInstance; // Use CartInstance
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
// use AndreiLungeanu\SimpleCart\SimpleCart; // No longer used

class DiscountCalculator
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
        // Calculate fixed discounts first as they might affect the base for percentage discounts
        $fixedDiscountTotal = $cart->getDiscounts()
            ->where('type', 'fixed')
            ->sum(fn(DiscountDTO $d) => $this->calculateFixed($d));

        // Calculate percentage discounts based on subtotal *after* fixed discounts
        $percentageDiscountTotal = $cart->getDiscounts()
            ->where('type', 'percentage')
            ->sum(fn(DiscountDTO $d) => $this->calculatePercentage($d, $subtotal - $fixedDiscountTotal)); // Pass adjusted subtotal

        // Calculate shipping discounts (logic might depend on ShippingCalculator)
        $shippingDiscountTotal = $cart->getDiscounts()
            ->where('type', 'shipping')
            ->sum(fn(DiscountDTO $d) => $this->calculateShipping($d, $cart)); // Pass cart for potential shipping info access

        // Sum all discount types
        // Note: Ensure discounts don't make total negative if needed.
        return round($fixedDiscountTotal + $percentageDiscountTotal + $shippingDiscountTotal, 2);

        /* Old logic:
        return $cart->getDiscounts()->sum(function (DiscountDTO $discount) use ($cart, $subtotal) { // Pass subtotal
            return match ($discount->type) {
                'fixed' => $this->calculateFixed($discount),
                'percentage' => $this->calculatePercentage($discount, $subtotal), // Pass subtotal
                'shipping' => $this->calculateShipping($discount, $cart), // Still needs cart instance
            };
        });
        */
    }

    // Fixed discount calculation is straightforward
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
        // Ensure base amount isn't negative if discounts exceed subtotal
        $effectiveBase = max(0, $baseAmount);
        return ($effectiveBase * $discount->value) / 100;
    }

    /**
     * Calculate shipping discount.
     * Needs access to shipping cost, potentially via CartInstance or injected ShippingCalculator.
     *
     * @param DiscountDTO $discount
     * @param CartInstance $cart
     * @return float
     */
    private function calculateShipping(DiscountDTO $discount, CartInstance $cart): float
    {
        // TODO: Implement actual shipping discount logic.
        // This might involve getting the calculated shipping cost first.
        // For now, return the discount value directly if it's fixed,
        // or 0 if it's meant to be a percentage of shipping (needs shipping cost).
        // Example: If discount value represents a fixed amount off shipping.
        return $discount->value; // Placeholder
    }
}
