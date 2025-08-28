<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;

/**
 * Interface for calculation services that operate on a CartInstance.
 */
interface CalculationInterface
{
    /**
     * Perform a calculation based on the cart instance.
     */
    public function calculate(CartInstance $cart): float;
}
