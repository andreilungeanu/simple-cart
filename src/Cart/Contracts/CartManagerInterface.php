<?php

namespace AndreiLungeanu\SimpleCart\Cart\Contracts;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\FluentCart;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO;

/**
 * Interface for managing cart instances.
 * This will orchestrate cart operations like creation, loading, saving,
 * and applying business logic previously handled by SimpleCart and CartInstance.
 */
interface CartManagerInterface
{
    /**
     * Find and retrieve a fluent wrapper for a cart instance by its ID.
     */
    public function find(string $cartId): ?FluentCart;

    /**
     * Find a fluent wrapper for a cart instance by ID or throw an exception if not found.
     */
    public function findOrFail(string $cartId): FluentCart;

    /**
     * Create a new, empty cart instance.
     */
    public function create(?string $cartId = null, ?string $userId = null, ?string $taxZone = null): FluentCart;

    /**
     * Add an item to a specific cart.
     */
    public function addItem(string $cartId, CartItemDTO|array $item): CartInstance;

    /**
     * Remove an item from a specific cart.
     */
    public function removeItem(string $cartId, string $itemId): CartInstance;

    /**
     * Update the quantity of an item in a specific cart.
     */
    public function updateQuantity(string $cartId, string $itemId, int $quantity): CartInstance;

    /**
     * Apply a discount to a specific cart.
     */
    public function applyDiscount(string $cartId, DiscountDTO|array $discount): CartInstance;

    /**
     * Remove a discount from a specific cart by its code.
     */
    public function removeDiscount(string $cartId, string $code): CartInstance;

    /**
     * Add an extra cost to a specific cart.
     */
    public function addExtraCost(string $cartId, ExtraCostDTO|array $cost): CartInstance;

    /**
     * Remove an extra cost from a specific cart by its name.
     */
    public function removeExtraCost(string $cartId, string $name): CartInstance;

    /**
     * Add a note to a specific cart.
     */
    public function addNote(string $cartId, string $note): CartInstance;

    /**
     * Set the shipping method and related info for a specific cart.
     */
    public function setShippingMethod(string $cartId, string $method, array $shippingInfo): CartInstance;

    /**
     * Set the VAT exemption status for a specific cart.
     */
    public function setVatExempt(string $cartId, bool $exempt = true): CartInstance;

    /**
     * Clear all items, discounts, etc., from a specific cart.
     */
    public function clear(string $cartId): CartInstance;

    /**
     * Delete a cart entirely.
     */
    public function destroy(string $cartId): bool;

    // --- Calculation Methods ---
    // These might stay in SimpleCart or move here depending on final design

    public function total(string $cartId): float;
    public function subtotal(string $cartId): float;
    public function taxAmount(string $cartId): float;
    public function shippingAmount(string $cartId): float;
    public function discountAmount(string $cartId): float;
    public function extraCostsTotal(string $cartId): float;
    public function itemCount(string $cartId): int;
    public function isFreeShippingApplied(string $cartId): bool;

    /**
     * Retrieve the underlying CartInstance data object directly.
     */
    public function getInstance(string $cartId): ?CartInstance;
}
