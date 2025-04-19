<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

interface TaxRateProvider
{
    public function getRate(CartDTO $cart): float;

    public function getRateForCategory(string $zone, string $category): ?float;

    public function getAvailableZones(): array;
}
