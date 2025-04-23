<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\SimpleCart;

interface TaxRateProvider
{
    public function getRate(SimpleCart $cart): float;

    public function getRateForCategory(string $zone, string $category): ?float;

    public function getAvailableZones(): array;
}
