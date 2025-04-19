<?php

namespace AndreiLungeanu\SimpleCart\Support;

trait MoneyCalculator
{
    protected function round(float $amount): float
    {
        return round($amount, 2);
    }

    protected function multiply(float $amount, float $multiplier): float
    {
        return $this->round($amount * $multiplier);
    }

    protected function add(float ...$amounts): float
    {
        return $this->round(array_sum(array_map(fn ($amount) => $this->round($amount), $amounts)));
    }

    protected function subtract(float $amount, float ...$subtractions): float
    {
        return $this->round($amount - array_sum(array_map(fn ($sub) => $this->round($sub), $subtractions)));
    }
}
