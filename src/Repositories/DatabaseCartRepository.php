<?php

namespace AndreiLungeanu\SimpleCart\Repositories;

use AndreiLungeanu\SimpleCart\Contracts\CartRepository; // Implement the contract
use AndreiLungeanu\SimpleCart\Models\Cart as CartModel; // Use Eloquent model alias
use AndreiLungeanu\SimpleCart\CartInstance; // Use the stateful object
use Illuminate\Support\Str;

class DatabaseCartRepository implements CartRepository
{
    /**
     * Find a cart by its ID and return it as a CartInstance object.
     *
     * @param string $id The ID of the cart to find.
     * @return CartInstance|null The found CartInstance or null if not found.
     */
    public function find(string $id): ?CartInstance
    {
        $cartModel = CartModel::find($id);

        if (! $cartModel) {
            return null;
        }

        // Instantiate CartInstance from the Eloquent model data
        // Assumes Eloquent model casts 'items', 'discounts', 'notes', 'extra_costs' to arrays
        $cartInstance = new CartInstance( // Instantiate first
            id: $cartModel->id,
            userId: $cartModel->user_id,
            taxZone: $cartModel->tax_zone,
            items: $cartModel->items ?? [],
            discounts: $cartModel->discounts ?? [],
            notes: $cartModel->notes ?? [],
            extraCosts: $cartModel->extra_costs ?? [],
            shippingMethod: $cartModel->shipping_method,
            vatExempt: $cartModel->vat_exempt ?? false,
            // shippingVatRate and shippingVatIncluded are not in constructor
        ); // $cartInstance is created here

        // Manually set properties not handled by constructor after instantiation
        $cartInstance->setShippingVatInfoInternal( // Call the setter on the created instance
            $cartModel->shipping_vat_rate, // Load from DB
            $cartModel->shipping_vat_included ?? false // Load from DB
        );

        return $cartInstance; // Return the instance *after* setting the properties
    }

    /**
     * Save the state of a CartInstance.
     * Should handle both creation and updates.
     *
     * @param CartInstance $cartInstance The cart instance to save.
     * @return string The ID of the saved cart.
     */
    public function save(CartInstance $cartInstance): string
    {
        $id = $cartInstance->getId(); // Get ID from the instance

        // Extract data from CartInstance for persistence
        // Assumes getters exist and return appropriate types (Collections need ->toArray())
        $dataToSave = [
            'id' => $id,
            'items' => $cartInstance->getItems()->map(fn($item) => $item->toArray())->toArray(), // Assuming DTOs have toArray()
            'discounts' => $cartInstance->getDiscounts()->map(fn($discount) => $discount->toArray())->toArray(), // Assuming DTOs have toArray()
            'notes' => $cartInstance->getNotes()->toArray(),
            'extra_costs' => $cartInstance->getExtraCosts()->map(fn($cost) => $cost->toArray())->toArray(), // Assuming DTOs have toArray()
            'user_id' => $cartInstance->getUserId(),
            'shipping_method' => $cartInstance->getShippingMethod(),
            'tax_zone' => $cartInstance->getTaxZone(),
            'vat_exempt' => $cartInstance->isVatExempt(),
            // Persist shipping VAT info
            'shipping_vat_rate' => $cartInstance->getShippingVatInfo()['rate'],
            'shipping_vat_included' => $cartInstance->getShippingVatInfo()['included'],
        ];

        CartModel::updateOrCreate(['id' => $id], $dataToSave);

        return $id;
    }

    /**
     * Delete a cart by its ID.
     *
     * @param string $id The ID of the cart to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $id): bool
    {
        // Cart::destroy returns the number of records deleted.
        return CartModel::destroy($id) > 0;
    }

    /**
     * Find carts associated with a user ID.
     * Note: This method is not part of the CartRepository interface.
     *
     * @param string $userId
     * @return \Illuminate\Support\Collection<int, CartInstance> Collection of CartInstance objects.
     */
    public function findByUser(string $userId): \Illuminate\Support\Collection
    {
        return CartModel::where('user_id', $userId)
            ->get()
            ->map(function (CartModel $cartModel) {
                // Instantiate CartInstance from the Eloquent model data
                return new CartInstance(
                    id: $cartModel->id,
                    userId: $cartModel->user_id,
                    taxZone: $cartModel->tax_zone,
                    items: $cartModel->items ?? [],
                    discounts: $cartModel->discounts ?? [],
                    notes: $cartModel->notes ?? [],
                    extraCosts: $cartModel->extra_costs ?? [],
                    shippingMethod: $cartModel->shipping_method,
                    vatExempt: $cartModel->vat_exempt ?? false
                );
            });
        // ->toArray(); // Remove toArray() to return a Collection of CartInstance
    }
}
