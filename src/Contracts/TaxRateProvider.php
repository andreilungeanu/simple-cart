<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

use AndreiLungeanu\SimpleCart\SimpleCart; // Import SimpleCart

interface TaxRateProvider
{
    // Change type hint from CartDTO to SimpleCart
    public function getRate(SimpleCart $cart): float;

    public function getRateForCategory(string $zone, string $category): ?float;

    public function getAvailableZones(): array;
}
