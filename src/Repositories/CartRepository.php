<?php

namespace AndreiLungeanu\SimpleCart\Repositories;

interface CartRepository
{
    public function find(string $id): ?array;

    public function save(array $cartData): string;

    public function delete(string $id): void;

    public function findByUser(string $userId): array;
}
