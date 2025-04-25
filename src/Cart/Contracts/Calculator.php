<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;

/**
 * Interface for calculation services that operate on a CartInstance.
 */
interface Calculator
{
    /**
     * Perform a calculation based on the cart instance.
     *
     * @param CartInstance $cart The cart instance to calculate for.
     * @return float The calculated value.
     */
    public function calculate(CartInstance $cart): float;
}
