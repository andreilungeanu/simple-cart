<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\Cart\Contracts\CartManagerInterface;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;

/**
 * Class SimpleCart (Facade Target / Entry Point)
 * Provides a stateless API for interacting with CartInstances by delegating to CartManager.
 */
class SimpleCart
{
    public function __construct(
        protected readonly CartManagerInterface $cartManager
    ) {}

    /**
     * Find and retrieve a fluent wrapper for a cart instance by its ID.
     */
    public function find(string $cartId): ?FluentCart
    {
        return $this->cartManager->find($cartId);
    }

    /**
     * Find a fluent wrapper for a cart instance by ID or throw an exception if not found.
     */
    public function findOrFail(string $cartId): FluentCart
    {
        return $this->cartManager->findOrFail($cartId);
    }

    /**
     * Create a new, empty cart instance.
     */
    public function create(?string $cartId = null, ?string $userId = null, ?string $taxZone = null): FluentCart
    {
        return $this->cartManager->create($cartId, $userId, $taxZone);
    }

    /**
     * Add an item to a specific cart.
     */
    public function addItem(string $cartId, CartItemDTO|array $item): CartInstance
    {
        return $this->cartManager->addItem($cartId, $item);
    }

    /**
     * Remove an item from a specific cart.
     */
    public function removeItem(string $cartId, string $itemId): CartInstance
    {
        return $this->cartManager->removeItem($cartId, $itemId);
    }

    /**
     * Update the quantity of an item in a specific cart.
     */
    public function updateQuantity(string $cartId, string $itemId, int $quantity): CartInstance
    {
        return $this->cartManager->updateQuantity($cartId, $itemId, $quantity);
    }

    /**
     * Clear all items, discounts, etc., from a specific cart.
     */
    public function clear(string $cartId): CartInstance
    {
        return $this->cartManager->clear($cartId);
    }

    /**
     * Delete a cart entirely.
     */
    public function destroy(string $cartId): bool
    {
        return $this->cartManager->destroy($cartId);
    }

    /**
     * Get the total value of a specific cart.
     */
    public function total(string $cartId): float
    {
        return $this->cartManager->total($cartId);
    }

    /**
     * Get the subtotal value of a specific cart.
     */
    public function subtotal(string $cartId): float
    {
        return $this->cartManager->subtotal($cartId);
    }

    /**
     * Get the total tax amount for a specific cart.
     */
    public function taxAmount(string $cartId): float
    {
        return $this->cartManager->taxAmount($cartId);
    }

    /**
     * Get the total shipping amount for a specific cart.
     */
    public function shippingAmount(string $cartId): float
    {
        return $this->cartManager->shippingAmount($cartId);
    }

    /**
     * Get the total discount amount for a specific cart.
     */
    public function discountAmount(string $cartId): float
    {
        return $this->cartManager->discountAmount($cartId);
    }

    /**
     * Get the total amount of extra costs for a specific cart.
     */
    public function extraCostsTotal(string $cartId): float
    {
        return $this->cartManager->extraCostsTotal($cartId);
    }

    /**
     * Get the total number of items (sum of quantities) in a specific cart.
     */
    public function itemCount(string $cartId): int
    {
        return $this->cartManager->itemCount($cartId);
    }

    /**
     * Check if free shipping is applied to a specific cart.
     */
    public function isFreeShippingApplied(string $cartId): bool
    {
        return $this->cartManager->isFreeShippingApplied($cartId);
    }

    /**
     * Apply a discount to a specific cart.
     */
    public function applyDiscount(string $cartId, DiscountDTO|array $discount): CartInstance
    {
        return $this->cartManager->applyDiscount($cartId, $discount);
    }

    /**
     * Remove a discount from a specific cart by its code.
     */
    public function removeDiscount(string $cartId, string $code): CartInstance
    {
        return $this->cartManager->removeDiscount($cartId, $code);
    }

    /**
     * Add a note to a specific cart.
     */
    public function addNote(string $cartId, string $note): CartInstance
    {
        return $this->cartManager->addNote($cartId, $note);
    }

    /**
     * Remove an extra cost from a specific cart by its name.
     */
    public function removeExtraCost(string $cartId, string $name): CartInstance
    {
        return $this->cartManager->removeExtraCost($cartId, $name);
    }

    /**
     * Add an extra cost to a specific cart.
     */
    public function addExtraCost(string $cartId, ExtraCostDTO|array $cost): CartInstance
    {
        return $this->cartManager->addExtraCost($cartId, $cost);
    }

    /**
     * Set the shipping method and related info for a specific cart.
     */
    public function setShippingMethod(string $cartId, string $method, array $shippingInfo): CartInstance
    {
        return $this->cartManager->setShippingMethod($cartId, $method, $shippingInfo);
    }

    /**
     * Set the VAT exemption status for a specific cart.
     */
    public function setVatExempt(string $cartId, bool $exempt = true): CartInstance
    {
        return $this->cartManager->setVatExempt($cartId, $exempt);
    }
}
