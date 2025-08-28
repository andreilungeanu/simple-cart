<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;

interface CartRepositoryInterface
{
    public function find(string $id): ?CartInstance;

    public function save(CartInstance $cartInstance): string;

    public function delete(string $id): bool;
}
