<?php

namespace AndreiLungeanu\SimpleCart\Repositories;

use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

interface CartRepository
{
    public function find(string $id): ?CartDTO;

    public function save(CartDTO $cart): string;

    public function delete(string $id): void;

    public function findByUser(string $userId): array;
}
