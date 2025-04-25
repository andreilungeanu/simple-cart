<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

interface InventoryChecker
{
    public function check(string $itemId): int;
}
