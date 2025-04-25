<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;

interface DiscountCalculatorInterface
{
    /**
     * Calculate the total discount amount for the cart.
     *
     * @param CartInstance $cart The cart instance.
     * @param float $subtotal The pre-calculated subtotal (items total before discounts).
     * @return float The total discount amount.
     */
    public function calculate(CartInstance $cart, float $subtotal): float;
}
