<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\Cart\Contracts\CartManagerInterface;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO;
use Illuminate\Contracts\Foundation\Application;

/**
 * A fluent wrapper around a specific cart instance, identified by its ID.
 * Provides chained methods for modifying the cart via the CartManager.
 */
class FluentCart
{
    /**
     * The underlying CartManager instance.
     */
    protected CartManagerInterface $cartManager;

    /**
     * Create a new fluent cart wrapper instance.
     */
    public function __construct(
        public readonly string $cartId,
        ?Application $app = null
    ) {
        $appInstance = $app ?? app();
        $this->cartManager = $appInstance->make(CartManagerInterface::class);
    }

    /**
     * Get the ID of the cart instance being managed.
     */
    public function getId(): string
    {
        return $this->cartId;
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(CartItemDTO|array $item): static
    {
        $this->cartManager->addItem($this->cartId, $item);

        return $this;
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(string $itemId): static
    {
        $this->cartManager->removeItem($this->cartId, $itemId);

        return $this;
    }

    /**
     * Update the quantity of an item in the cart.
     */
    public function updateQuantity(string $itemId, int $quantity): static
    {
        $this->cartManager->updateQuantity($this->cartId, $itemId, $quantity);

        return $this;
    }

    /**
     * Clear the cart's contents.
     */
    public function clear(): static
    {
        $this->cartManager->clear($this->cartId);

        return $this;
    }

    /**
     * Apply a discount to the cart.
     */
    public function applyDiscount(DiscountDTO|array $discount): static
    {
        $this->cartManager->applyDiscount($this->cartId, $discount);

        return $this;
    }

    /**
     * Remove a discount from the cart by its code.
     */
    public function removeDiscount(string $code): static
    {
        $this->cartManager->removeDiscount($this->cartId, $code);

        return $this;
    }

    /**
     * Add a note to the cart.
     */
    public function addNote(string $note): static
    {
        $this->cartManager->addNote($this->cartId, $note);

        return $this;
    }

    /**
     * Remove an extra cost from the cart by its name.
     */
    public function removeExtraCost(string $name): static
    {
        $this->cartManager->removeExtraCost($this->cartId, $name);

        return $this;
    }

    /**
     * Add an extra cost to the cart.
     */
    public function addExtraCost(ExtraCostDTO|array $cost): static
    {
        $this->cartManager->addExtraCost($this->cartId, $cost);

        return $this;
    }

    /**
     * Set the shipping method for the cart.
     */
    public function setShippingMethod(string $method, array $shippingInfo): static
    {
        $this->cartManager->setShippingMethod($this->cartId, $method, $shippingInfo);

        return $this;
    }

    /**
     * Set the VAT exemption status for the cart.
     */
    public function setVatExempt(bool $exempt = true): static
    {
        $this->cartManager->setVatExempt($this->cartId, $exempt);

        return $this;
    }

    /**
     * Retrieve the underlying CartInstance data object.
     */
    public function getInstance(): ?CartInstance
    {
        return $this->cartManager->getInstance($this->cartId);
    }
}
