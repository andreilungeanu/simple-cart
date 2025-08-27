<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;

interface CartCalculatorInterface
{
    public function getSubtotal(CartInstance $cart): float;

    public function getItemCount(CartInstance $cart): int;

    public function getShippingAmount(CartInstance $cart): float;

    public function getTaxAmount(CartInstance $cart): float;

    public function getDiscountAmount(CartInstance $cart): float;

    public function getTotal(CartInstance $cart): float;

    public function getExtraCostsTotal(CartInstance $cart): float;

    public function calculateShippingVat(CartInstance $cart): float;

    public function isFreeShippingApplied(CartInstance $cart): bool;
}
