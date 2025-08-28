<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\Cart\DTOs\ShippingRateDTO;
use AndreiLungeanu\SimpleCart\CartInstance;

interface ShippingCalculatorInterface extends CalculationInterface
{
    /**
     * Get the shipping cost for the given cart.
     *
     * @param  CartInstance  $cart  The cart instance.
     * @return float The calculated shipping cost.
     */
    public function getShippingAmount(CartInstance $cart): float;

    /**
     * Get detailed shipping information (rate, VAT info) for the cart's selected method.
     */
    public function getShippingInfo(CartInstance $cart): ?ShippingRateDTO;
}
