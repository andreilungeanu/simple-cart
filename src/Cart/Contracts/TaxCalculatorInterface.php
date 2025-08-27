<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;

interface TaxCalculatorInterface extends Calculator
{
    /**
     * Calculate the total tax amount for the cart items.
     * (Does not include shipping or extra cost tax).
     *
     * @param  CartInstance  $cart  The cart instance.
     * @return float The calculated tax amount for items.
     */
    public function calculate(CartInstance $cart): float;
}
