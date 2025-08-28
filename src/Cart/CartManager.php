<?php

namespace AndreiLungeanu\SimpleCart\Cart;

use AndreiLungeanu\SimpleCart\Cart\Contracts\AddItemToCartActionInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\CartCalculatorInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\CartManagerInterface;
use AndreiLungeanu\SimpleCart\Cart\Contracts\CartRepository;
use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Cart\Events\CartCleared;
use AndreiLungeanu\SimpleCart\Cart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Cart\Events\CartDeleted;
use AndreiLungeanu\SimpleCart\Cart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Cart\Exceptions\CartException;
use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\FluentCart;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

class CartManager implements CartManagerInterface
{
    public function __construct(
        protected readonly CartRepository $repository,
        protected readonly CartCalculatorInterface $calculator,
        protected readonly AddItemToCartActionInterface $addItemAction,
        protected readonly Dispatcher $events
    ) {}

    /**
     * Retrieve the underlying CartInstance data object directly.
     */
    public function getInstance(string $cartId): ?CartInstance
    {
        return $this->repository->find($cartId);
    }

    /**
     * Find and retrieve a fluent wrapper for a cart instance by its ID.
     */
    public function find(string $cartId): ?FluentCart
    {
        $cartInstance = $this->getInstance($cartId);

        return $cartInstance ? new FluentCart($cartId) : null;
    }

    /**
     * Find a fluent wrapper for a cart instance by ID or throw an exception if not found.
     */
    public function findOrFail(string $cartId): FluentCart
    {
        $wrapper = $this->find($cartId);
        if (! $wrapper) {
            throw new CartException("Cart with ID [{$cartId}] not found.");
        }

        return $wrapper;
    }

    /**
     * Create a new, empty cart instance.
     */
    public function create(?string $cartId = null, ?string $userId = null, ?string $taxZone = null): FluentCart
    {
        $id = $cartId ?: (string) Str::uuid();

        $cart = new CartInstance(
            id: $id,
            userId: $userId,
            taxZone: $taxZone
        );

        $this->repository->save($cart);
        $this->events->dispatch(new CartCreated($cart));

        return new FluentCart($id);
    }

    /**
     * Add an item to a specific cart.
     */
    public function addItem(string $cartId, CartItemDTO|array $item): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for addItem.");
        }

        $itemDTO = $item instanceof CartItemDTO ? $item : CartItemDTO::fromArray($item);

        // TODO: Refactor to use a dedicated AddItem action if injected
        $updatedCart = ($this->addItemAction)($cartInstance, $itemDTO);

        $this->repository->save($updatedCart);
        $this->events->dispatch(new CartUpdated($updatedCart));

        return $updatedCart;
    }

    /**
     * Remove an item from a specific cart.
     */
    public function removeItem(string $cartId, string $itemId): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for removeItem.");
        }

        $initialItems = $cartInstance->getItems();
        $initialCount = $initialItems->count();

        $updatedItems = $initialItems->filter(fn (CartItemDTO $item) => $item->id !== $itemId);

        if ($updatedItems->count() < $initialCount) {
            $cartInstance->setItems($updatedItems);
            $this->repository->save($cartInstance);
            $this->events->dispatch(new CartUpdated($cartInstance));
        }

        return $cartInstance;
    }

    /**
     * Update the quantity of an item in a specific cart.
     */
    public function updateQuantity(string $cartId, string $itemId, int $quantity): CartInstance
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive. Use removeItem to remove.');
        }

        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for updateQuantity.");
        }

        $updated = false;
        $updatedItems = $cartInstance->getItems()->map(function (CartItemDTO $item) use ($itemId, $quantity, &$updated) {
            if ($item->id === $itemId) {
                $updated = true;

                return $item->withQuantity($quantity);
            }

            return $item;
        });

        if (! $updated) {
            throw new CartException("Item with ID {$itemId} not found in cart {$cartId} for updateQuantity.");
        }

        $cartInstance->setItems($updatedItems);
        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance;
    }

    /**
     * Apply a discount to a specific cart.
     */
    public function applyDiscount(string $cartId, DiscountDTO|array $discount): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for applyDiscount.");
        }

        $discountDTO = $discount instanceof DiscountDTO ? $discount : DiscountDTO::fromArray($discount);

        $discounts = $cartInstance->getDiscounts();
        $discounts->push($discountDTO);
        $cartInstance->setDiscounts($discounts);

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance;
    }

    /**
     * Remove a discount from a specific cart by its code.
     */
    public function removeDiscount(string $cartId, string $code): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for removeDiscount.");
        }

        $initialDiscounts = $cartInstance->getDiscounts();
        $initialCount = $initialDiscounts->count();

        $updatedDiscounts = $initialDiscounts->filter(fn (DiscountDTO $discount) => $discount->code !== $code);

        if ($updatedDiscounts->count() < $initialCount) {
            $cartInstance->setDiscounts($updatedDiscounts);
            $this->repository->save($cartInstance);
            $this->events->dispatch(new CartUpdated($cartInstance));
        }

        return $cartInstance;
    }

    /**
     * Add an extra cost to a specific cart.
     */
    public function addExtraCost(string $cartId, ExtraCostDTO|array $cost): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for addExtraCost.");
        }

        $extraCostDTO = $cost instanceof ExtraCostDTO ? $cost : ExtraCostDTO::fromArray($cost);

        $extraCosts = $cartInstance->getExtraCosts();
        $extraCosts->push($extraCostDTO);
        $cartInstance->setExtraCosts($extraCosts);

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance;
    }

    /**
     * Remove an extra cost from a specific cart by its name.
     */
    public function removeExtraCost(string $cartId, string $name): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for removeExtraCost.");
        }

        $initialCosts = $cartInstance->getExtraCosts();
        $initialCount = $initialCosts->count();

        $updatedCosts = $initialCosts->filter(fn (ExtraCostDTO $cost) => $cost->name !== $name);

        if ($updatedCosts->count() < $initialCount) {
            $cartInstance->setExtraCosts($updatedCosts);
            $this->repository->save($cartInstance);
            $this->events->dispatch(new CartUpdated($cartInstance));
        }

        return $cartInstance;
    }

    /**
     * Add a note to a specific cart.
     */
    public function addNote(string $cartId, string $note): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for addNote.");
        }

        $notes = $cartInstance->getNotes();
        $notes->push($note);
        $cartInstance->setNotes($notes);

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance;
    }

    /**
     * Set the shipping method and related info for a specific cart.
     */
    public function setShippingMethod(string $cartId, string $method, array $shippingInfo): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for setShippingMethod.");
        }

        if (array_key_exists('vat_rate', $shippingInfo) && is_numeric($shippingInfo['vat_rate'])) {
            if ($shippingInfo['vat_rate'] < 0 || $shippingInfo['vat_rate'] > 1) {
                throw new \InvalidArgumentException('VAT rate must be between 0 and 1');
            }
        }

        $cartInstance->setShippingVatInfoInternal(
            $shippingInfo['vat_rate'] ?? null,
            $shippingInfo['vat_included'] ?? false
        );
        $cartInstance->setShippingMethodInternal($method);

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance;
    }

    /**
     * Set the VAT exemption status for a specific cart.
     */
    public function setVatExempt(string $cartId, bool $exempt = true): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for setVatExempt.");
        }

        $cartInstance->setVatExemptInternal($exempt);

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance;
    }

    /**
     * Clear all items, discounts, etc., from a specific cart.
     */
    public function clear(string $cartId): CartInstance
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for clear.");
        }

        $cartInstance->setItems(collect([]));
        $cartInstance->setDiscounts(collect([]));
        $cartInstance->setNotes(collect([]));
        $cartInstance->setExtraCosts(collect([]));
        $cartInstance->setShippingMethodInternal(null);
        $cartInstance->setShippingVatInfoInternal(null, false);
        $cartInstance->setVatExemptInternal(false);

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartCleared($cartId));

        return $cartInstance;
    }

    /**
     * Delete a cart entirely.
     */
    public function destroy(string $cartId): bool
    {
        $deleted = $this->repository->delete($cartId);

        if ($deleted) {
            $this->events->dispatch(new CartDeleted($cartId));
        }

        return $deleted;
    }

    public function total(string $cartId): float
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for total calculation.");
        }

        return $this->calculator->getTotal($cartInstance);
    }

    public function subtotal(string $cartId): float
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for subtotal calculation.");
        }

        return $this->calculator->getSubtotal($cartInstance);
    }

    public function taxAmount(string $cartId): float
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for taxAmount calculation.");
        }

        return $this->calculator->getTaxAmount($cartInstance);
    }

    public function shippingAmount(string $cartId): float
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for shippingAmount calculation.");
        }

        return $this->calculator->getShippingAmount($cartInstance);
    }

    public function discountAmount(string $cartId): float
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for discountAmount calculation.");
        }

        return $this->calculator->getDiscountAmount($cartInstance);
    }

    public function extraCostsTotal(string $cartId): float
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for extraCostsTotal calculation.");
        }

        return $this->calculator->getExtraCostsTotal($cartInstance);
    }

    public function itemCount(string $cartId): int
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for itemCount calculation.");
        }

        return $this->calculator->getItemCount($cartInstance);
    }

    public function isFreeShippingApplied(string $cartId): bool
    {
        $cartInstance = $this->getInstance($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for isFreeShippingApplied check.");
        }

        return $this->calculator->isFreeShippingApplied($cartInstance);
    }
}
