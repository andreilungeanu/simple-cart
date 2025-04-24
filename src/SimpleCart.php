<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\CartInstance;
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Events\CartCleared;
use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use AndreiLungeanu\SimpleCart\Repositories\CartRepository;
use AndreiLungeanu\SimpleCart\Repositories\DatabaseCartRepository;
use AndreiLungeanu\SimpleCart\Services\CartCalculator;
use AndreiLungeanu\SimpleCart\Actions\AddItemToCartAction;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

/**
 * Class SimpleCart (Manager)
 * Provides a stateless API for interacting with CartInstances.
 * This is the intended target for the SimpleCart Facade.
 */
class SimpleCart
{
    public function __construct(
        protected readonly DatabaseCartRepository $repository,
        protected readonly CartCalculator $calculator,
        protected readonly AddItemToCartAction $addItemAction,
        protected readonly Dispatcher $events
    ) {}

    /**
     * Find and retrieve a fluent wrapper for a cart instance by its ID.
     *
     * @param string $cartId
     * @return FluentCart|null Returns wrapper if found, null otherwise.
     */
    public function find(string $cartId): ?FluentCart // Return FluentCart wrapper
    {
        // Check if cart exists in repository first
        $cartInstance = $this->repository->find($cartId);

        // If found, return a new FluentCart wrapper for it
        return $cartInstance ? new FluentCart($cartId) : null;
    }

    /**
     * Find a fluent wrapper for a cart instance by ID or throw an exception if not found.
     *
     * @param string $cartId
     * @return FluentCart
     * @throws CartException
     */
    public function findOrFail(string $cartId): FluentCart // Return FluentCart wrapper
    {
        $wrapper = $this->find($cartId); // Use the updated find method
        if (! $wrapper) {
            throw new CartException("Cart with ID [{$cartId}] not found.");
        }
        return $wrapper;
    }

    /**
     * Create a new, empty cart instance.
     *
     * @param string|null $cartId Provide an ID or let it be generated.
     * @param string|null $userId Optional user ID.
     * @param string|null $taxZone Optional tax zone.
     * @return FluentCart A fluent wrapper for the newly created cart instance.
     */
    public function create(?string $cartId = null, ?string $userId = null, ?string $taxZone = null): FluentCart // Return FluentCart wrapper
    {
        $id = $cartId ?: (string) Str::uuid();

        // Create a new CartInstance object - Constructor needs simplification later
        // For now, we pass dummy dependencies which CartInstance constructor expects,
        // but these won't actually be used by CartInstance once refactored.
        // This highlights the need to refactor CartInstance constructor next.
        // Corrected: Removed invalid arguments (repository, calculator, addItemAction)
        $cart = new CartInstance(
            id: $id,
            userId: $userId,
            taxZone: $taxZone
        );

        // Persist the newly created cart
        $this->repository->save($cart); // Repository needs to handle CartInstance

        // Dispatch event
        $this->events->dispatch(new CartCreated($cart)); // Pass the CartInstance to the event

        // Return a FluentCart wrapper for the new cart
        return new FluentCart($id);
    }

    /**
     * Add an item to a specific cart.
     *
     * @param string $cartId
     * @param CartItemDTO|array $item
     * @return CartInstance The updated cart instance.
     * @throws CartException
     */
    public function addItem(string $cartId, CartItemDTO|array $item): CartInstance
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for addItem.");
        }

        $itemDTO = $item instanceof CartItemDTO ? $item : CartItemDTO::fromArray($item);

        // Delegate to the action, passing the CartInstance
        $updatedCart = ($this->addItemAction)($cartInstance, $itemDTO);

        // Persist changes
        $this->repository->save($updatedCart);

        // Dispatch event
        $this->events->dispatch(new CartUpdated($updatedCart));

        return $updatedCart;
    }

    /**
     * Remove an item from a specific cart.
     *
     * @param string $cartId
     * @param string $itemId
     * @return CartInstance The updated cart instance.
     * @throws CartException
     */
    public function removeItem(string $cartId, string $itemId): CartInstance
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for removeItem.");
        }

        $initialCount = $cartInstance->getItems()->count();
        // Directly modify the CartInstance state
        $cartInstance->removeItem($itemId); // Call method on CartInstance

        if ($cartInstance->getItems()->count() < $initialCount) {
            $this->repository->save($cartInstance);
            $this->events->dispatch(new CartUpdated($cartInstance));
        }

        return $cartInstance; // Return the modified CartInstance
    }

    /**
     * Update the quantity of an item in a specific cart.
     *
     * @param string $cartId
     * @param string $itemId
     * @param int $quantity
     * @return CartInstance The updated cart instance.
     * @throws CartException|\InvalidArgumentException
     */
    public function updateQuantity(string $cartId, string $itemId, int $quantity): CartInstance
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive. Use removeItem to remove.');
        }

        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for updateQuantity.");
        }

        $cartInstance->updateQuantity($itemId, $quantity); // Call method on CartInstance

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance; // Return the modified CartInstance
    }

    /**
     * Clear all items, discounts, etc., from a specific cart.
     *
     * @param string $cartId
     * @return CartInstance The cleared (but potentially still existing) cart instance.
     * @throws CartException
     */
    public function clear(string $cartId): CartInstance
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for clear.");
        }

        $cartInstance->clear(); // Call method on CartInstance

        $this->repository->save($cartInstance); // Save the cleared state
        $this->events->dispatch(new CartCleared($cartId));

        return $cartInstance; // Return the modified CartInstance
    }

    /**
     * Delete a cart entirely.
     *
     * @param string $cartId
     * @return bool Success status from repository.
     */
    public function destroy(string $cartId): bool
    {
        $deleted = $this->repository->delete($cartId);
        return $deleted;
    }

    // --- Calculation Methods ---

    /**
     * Get the total value of a specific cart.
     *
     * @param string $cartId
     * @return float
     * @throws CartException
     */
    public function total(string $cartId): float
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for total calculation.");
        }
        return $this->calculator->getTotal($cartInstance);
    }

    /**
     * Get the subtotal value of a specific cart.
     *
     * @param string $cartId
     * @return float
     * @throws CartException
     */
    public function subtotal(string $cartId): float
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for subtotal calculation.");
        }
        return $this->calculator->getSubtotal($cartInstance);
    }

    /**
     * Get the total tax amount for a specific cart.
     *
     * @param string $cartId
     * @return float
     * @throws CartException
     */
    public function taxAmount(string $cartId): float
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for taxAmount calculation.");
        }
        return $this->calculator->getTaxAmount($cartInstance);
    }

    /**
     * Get the total shipping amount for a specific cart.
     *
     * @param string $cartId
     * @return float
     * @throws CartException
     */
    public function shippingAmount(string $cartId): float
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for shippingAmount calculation.");
        }
        return $this->calculator->getShippingAmount($cartInstance);
    }

    /**
     * Get the total discount amount for a specific cart.
     *
     * @param string $cartId
     * @return float
     * @throws CartException
     */
    public function discountAmount(string $cartId): float
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for discountAmount calculation.");
        }
        return $this->calculator->getDiscountAmount($cartInstance);
    }

    /**
     * Get the total amount of extra costs for a specific cart.
     *
     * @param string $cartId
     * @return float
     * @throws CartException
     */
    public function extraCostsTotal(string $cartId): float
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for extraCostsTotal calculation.");
        }
        return $this->calculator->getExtraCostsTotal($cartInstance);
    }

    /**
     * Get the total number of items (sum of quantities) in a specific cart.
     *
     * @param string $cartId
     * @return int
     * @throws CartException
     */
    public function itemCount(string $cartId): int
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for itemCount calculation.");
        }
        return $this->calculator->getItemCount($cartInstance);
    }

    /**
     * Check if free shipping is applied to a specific cart.
     *
     * @param string $cartId
     * @return bool
     * @throws CartException
     */
    public function isFreeShippingApplied(string $cartId): bool
    {
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for isFreeShippingApplied check.");
        }
        return $this->calculator->isFreeShippingApplied($cartInstance);
    }

    // --- Other Operations ---

    /**
     * Apply a discount to a specific cart.
     *
     * @param string $cartId
     * @param DiscountDTO|array $discount
     * @return CartInstance
     * @throws CartException|\InvalidArgumentException
     */
    public function applyDiscount(string $cartId, DiscountDTO|array $discount): CartInstance
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for applyDiscount.");
        }

        $discountDTO = $discount instanceof DiscountDTO ? $discount : DiscountDTO::fromArray($discount);
        $cartInstance->applyDiscount($discountDTO); // Call method on CartInstance

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance; // Return the modified CartInstance
    }

    /**
     * Add a note to a specific cart.
     *
     * @param string $cartId
     * @param string $note
     * @return CartInstance
     * @throws CartException
     */
    public function addNote(string $cartId, string $note): CartInstance
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for addNote.");
        }

        $cartInstance->addNote($note); // Call method on CartInstance

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance; // Return the modified CartInstance
    }

    /**
     * Add an extra cost to a specific cart.
     *
     * @param string $cartId
     * @param ExtraCostDTO|array $cost
     * @return CartInstance
     * @throws CartException|\InvalidArgumentException
     */
    public function addExtraCost(string $cartId, ExtraCostDTO|array $cost): CartInstance
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for addExtraCost.");
        }

        $extraCostDTO = $cost instanceof ExtraCostDTO ? $cost : ExtraCostDTO::fromArray($cost);
        $cartInstance->addExtraCost($extraCostDTO); // Call method on CartInstance

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance; // Return the modified CartInstance
    }

    /**
     * Set the shipping method and related info for a specific cart.
     *
     * @param string $cartId
     * @param string $method
     * @param array $shippingInfo (e.g., ['vat_rate' => 0.19, 'vat_included' => false])
     * @return CartInstance
     * @throws CartException|\InvalidArgumentException
     */
    public function setShippingMethod(string $cartId, string $method, array $shippingInfo): CartInstance
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for setShippingMethod.");
        }

        $cartInstance->setShippingMethod($method, $shippingInfo); // Call method on CartInstance

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance; // Return the modified CartInstance
    }

    /**
     * Set the VAT exemption status for a specific cart.
     *
     * @param string $cartId
     * @param bool $exempt
     * @return CartInstance
     * @throws CartException
     */
    public function setVatExempt(string $cartId, bool $exempt = true): CartInstance
    {
        // Fetch CartInstance directly from repository
        $cartInstance = $this->repository->find($cartId);
        if (! $cartInstance) {
            throw new CartException("Cart with ID [{$cartId}] not found for setVatExempt.");
        }

        $cartInstance->setVatExempt($exempt); // Call method on CartInstance

        $this->repository->save($cartInstance);
        $this->events->dispatch(new CartUpdated($cartInstance));

        return $cartInstance; // Return the modified CartInstance
    }
}
