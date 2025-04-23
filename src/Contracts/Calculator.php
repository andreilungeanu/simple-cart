<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\SimpleCart; // Import SimpleCart

interface Calculator
{
    // Change type hint from CartDTO to SimpleCart
    public function calculate(SimpleCart $cart): float;
}
