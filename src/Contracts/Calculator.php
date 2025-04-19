<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

interface Calculator
{
    public function calculate(CartDTO $cart): float;
}
