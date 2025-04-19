<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

interface ShippingRateProvider
{
    public function getRate(CartDTO $cart, string $method): array;

    public function getAvailableMethods(CartDTO $cart): array;
}
