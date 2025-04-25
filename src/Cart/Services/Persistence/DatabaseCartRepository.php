<?php

namespace AndreiLungeanu\SimpleCart\Repositories;

use AndreiLungeanu\SimpleCart\Contracts\CartRepository;
use AndreiLungeanu\SimpleCart\Models\Cart as CartModel;
use AndreiLungeanu\SimpleCart\CartInstance;
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

        $cartInstance = new CartInstance(
            id: $cartModel->id,
            userId: $cartModel->user_id,
            taxZone: $cartModel->tax_zone,
            items: $cartModel->items ?? [],
            discounts: $cartModel->discounts ?? [],
            notes: $cartModel->notes ?? [],
            extraCosts: $cartModel->extra_costs ?? [],
            shippingMethod: $cartModel->shipping_method,
            vatExempt: $cartModel->vat_exempt ?? false,
        );

        $cartInstance->setShippingVatInfoInternal(
            $cartModel->shipping_vat_rate,
            $cartModel->shipping_vat_included ?? false
        );

        return $cartInstance;
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
        $id = $cartInstance->getId();

        $dataToSave = [
            'id' => $id,
            'items' => $cartInstance->getItems()->map(fn($item) => $item->toArray())->toArray(),
            'discounts' => $cartInstance->getDiscounts()->map(fn($discount) => $discount->toArray())->toArray(),
            'notes' => $cartInstance->getNotes()->toArray(),
            'extra_costs' => $cartInstance->getExtraCosts()->map(fn($cost) => $cost->toArray())->toArray(),
            'user_id' => $cartInstance->getUserId(),
            'shipping_method' => $cartInstance->getShippingMethod(),
            'tax_zone' => $cartInstance->getTaxZone(),
            'vat_exempt' => $cartInstance->isVatExempt(),
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
        return CartModel::destroy($id) > 0;
    }

    /**
     * Find carts associated with a user ID.
     *
     * @param string $userId
     * @return \Illuminate\Support\Collection<int, CartInstance> Collection of CartInstance objects.
     */
    public function findByUser(string $userId): \Illuminate\Support\Collection
    {
        return CartModel::where('user_id', $userId)
            ->get()
            ->map(function (CartModel $cartModel) {
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
    }
}
