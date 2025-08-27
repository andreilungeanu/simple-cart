<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;

interface ShippingCalculatorInterface extends Calculator
{
    /**
     * Calculate the shipping cost for the given cart.
     *
     * @param  CartInstance  $cart  The cart instance.
     * @return float The calculated shipping cost.
     */
    public function calculate(CartInstance $cart): float;

    /**
     * Get detailed shipping information (rate, VAT info) for the cart's selected method.
     *
     * @param  CartInstance  $cart  The cart instance.
     * @return array|null Shipping details or null if no method selected.
     */
    public function getShippingInfo(CartInstance $cart): ?array;
}
