<?php

namespace AndreiLungeanu\SimpleCart\Contracts;

interface InventoryChecker
{
    public function check(string $itemId): int;
}
