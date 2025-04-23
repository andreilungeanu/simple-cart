<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\SimpleCart; // Import SimpleCart

interface ShippingRateProvider
{
    // Change type hint from CartDTO to SimpleCart
    public function getRate(SimpleCart $cart, string $method): array;

    // Change type hint from CartDTO to SimpleCart
    public function getAvailableMethods(SimpleCart $cart): array;
}
