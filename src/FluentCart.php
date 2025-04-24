<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
use Illuminate\Contracts\Foundation\Application;

/**
 * A fluent wrapper around a specific cart instance, identified by its ID.
 * Provides chained methods for modifying the cart via the SimpleCart manager.
 */
class FluentCart
{
    /**
     * The underlying SimpleCart manager instance.
     */
    protected SimpleCart $manager;

    /**
     * The cart repository instance.
     */
    protected Repositories\DatabaseCartRepository $repository;

    /**
     * Create a new fluent cart wrapper instance.
     *
     * @param string $cartId The ID of the cart this wrapper represents.
     * @param Application|null $app Optional Laravel application instance for resolving the manager.
     */
    public function __construct(
        public readonly string $cartId,
        ?Application $app = null
    ) {
        $appInstance = $app ?? app();
        $this->manager = $appInstance->make(SimpleCart::class);
        $this->repository = $appInstance->make(Repositories\DatabaseCartRepository::class);
    }

    /**
     * Get the ID of the cart instance being managed.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->cartId;
    }

    /**
     * Add an item to the cart.
     *
     * @param CartItemDTO|array $item
     * @return $this
     */
    public function addItem(CartItemDTO|array $item): static
    {
        $this->manager->addItem($this->cartId, $item);
        return $this;
    }

    /**
     * Remove an item from the cart.
     *
     * @param string $itemId
     * @return $this
     */
    public function removeItem(string $itemId): static
    {
        $this->manager->removeItem($this->cartId, $itemId);
        return $this;
    }

    /**
     * Update the quantity of an item in the cart.
     *
     * @param string $itemId
     * @param int $quantity
     * @return $this
     */
    public function updateQuantity(string $itemId, int $quantity): static
    {
        $this->manager->updateQuantity($this->cartId, $itemId, $quantity);
        return $this;
    }

    /**
     * Clear the cart's contents.
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->manager->clear($this->cartId);
        return $this;
    }

    /**
     * Apply a discount to the cart.
     *
     * @param DiscountDTO|array $discount
     * @return $this
     */
    public function applyDiscount(DiscountDTO|array $discount): static
    {
        $this->manager->applyDiscount($this->cartId, $discount);
        return $this;
    }

    /**
     * Remove a discount from the cart by its code.
     *
     * @param string $code
     * @return $this
     */
    public function removeDiscount(string $code): static
    {
        $this->manager->removeDiscount($this->cartId, $code);
        return $this;
    }

    /**
     * Add a note to the cart.
     *
     * @param string $note
     * @return $this
     */
    public function addNote(string $note): static
    {
        $this->manager->addNote($this->cartId, $note);
        return $this;
    }

    /**
     * Remove an extra cost from the cart by its name.
     *
     * @param string $name
     * @return $this
     */
    public function removeExtraCost(string $name): static
    {
        $this->manager->removeExtraCost($this->cartId, $name);
        return $this;
    }

    /**
     * Add an extra cost to the cart.
     *
     * @param ExtraCostDTO|array $cost
     * @return $this
     */
    public function addExtraCost(ExtraCostDTO|array $cost): static
    {
        $this->manager->addExtraCost($this->cartId, $cost);
        return $this;
    }

    /**
     * Set the shipping method for the cart.
     *
     * @param string $method
     * @param array $shippingInfo
     * @return $this
     */
    public function setShippingMethod(string $method, array $shippingInfo): static
    {
        $this->manager->setShippingMethod($this->cartId, $method, $shippingInfo);
        return $this;
    }

    /**
     * Set the VAT exemption status for the cart.
     *
     * @param bool $exempt
     * @return $this
     */
    public function setVatExempt(bool $exempt = true): static
    {
        $this->manager->setVatExempt($this->cartId, $exempt);
        return $this;
    }

    /**
     * Retrieve the underlying CartInstance data object.
     *
     * @return CartInstance|null
     */
    public function getInstance(): ?CartInstance
    {
        return $this->repository->find($this->cartId);
    }
}
