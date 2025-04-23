<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\SimpleCart;

interface Calculator
{
    public function calculate(SimpleCart $cart): float;
}
