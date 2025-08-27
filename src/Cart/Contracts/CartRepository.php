<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;

interface CartRepository
{
    /**
     * Find a cart by its ID and return it as a CartInstance object.
     *
     * @param  string  $id  The ID of the cart to find.
     * @return CartInstance|null The found CartInstance or null if not found.
     */
    public function find(string $id): ?CartInstance;

    /**
     * Save the state of a CartInstance.
     * Should handle both creation and updates.
     *
     * @param  CartInstance  $cartInstance  The cart instance to save.
     * @return string The ID of the saved cart (might be the same or newly generated if applicable).
     */
    public function save(CartInstance $cartInstance): string;

    /**
     * Delete a cart by its ID.
     *
     * @param  string  $id  The ID of the cart to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $id): bool;
}
