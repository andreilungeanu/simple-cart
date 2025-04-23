<?php

namespace AndreiLungeanu\SimpleCart\Repositories;

// CartDTO is no longer needed here
// use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

interface CartRepository
{
    // Change return type to ?array
    public function find(string $id): ?array;

    // Change parameter type to array
    public function save(array $cartData): string;

    public function delete(string $id): void;

    public function findByUser(string $userId): array;
}
