<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\SimpleCart;

interface ShippingRateProvider
{
    public function getRate(SimpleCart $cart, string $method): array;

    public function getAvailableMethods(SimpleCart $cart): array;
}
