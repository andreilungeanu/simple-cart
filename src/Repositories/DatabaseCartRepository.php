<?php

namespace AndreiLungeanu\SimpleCart\Repositories;

// Remove DTO imports if no longer needed directly here
// use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
// use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Models\Cart;
use Illuminate\Support\Str;

class DatabaseCartRepository implements CartRepository
{
    // Change return type hint
    public function find(string $id): ?array
    {
        $cart = Cart::find($id);

        if (! $cart) {
            return null;
        }

        // Return raw data as an array
        return [
            'id' => $cart->id,
            // Decode JSON fields, defaulting to empty arrays
            'items' => is_string($cart->items) ? json_decode($cart->items, true) : ($cart->items ?? []),
            'discounts' => is_string($cart->discounts) ? json_decode($cart->discounts, true) : ($cart->discounts ?? []),
            'notes' => is_string($cart->notes) ? json_decode($cart->notes, true) : ($cart->notes ?? []),
            'extra_costs' => is_string($cart->extra_costs) ? json_decode($cart->extra_costs, true) : ($cart->extra_costs ?? []),
            'user_id' => $cart->user_id,
            'shipping_method' => $cart->shipping_method,
            'tax_zone' => $cart->tax_zone,
            // Include vat_exempt if stored, otherwise SimpleCart will handle it
            // 'vat_exempt' => $cart->vat_exempt ?? false,
        ];
    }

    // Change parameter type hint
    public function save(array $cartData): string
    {
        // Ensure ID exists
        $id = $cartData['id'] ?? (string) Str::uuid();

        // Prepare data for saving, ensuring collections/DTO arrays are encoded
        $dataToSave = [
            'id' => $id,
            // Assume items, discounts etc. are passed as arrays ready for encoding
            'items' => json_encode($cartData['items'] ?? []),
            'discounts' => json_encode($cartData['discounts'] ?? []),
            'notes' => json_encode($cartData['notes'] ?? []),
            'extra_costs' => json_encode($cartData['extra_costs'] ?? []),
            'user_id' => $cartData['user_id'] ?? null,
            'shipping_method' => $cartData['shipping_method'] ?? null,
            'tax_zone' => $cartData['tax_zone'] ?? null,
            // 'vat_exempt' => $cartData['vat_exempt'] ?? false, // Only if storing this flag
            // Remove calculated amounts - should be calculated on demand
            // 'tax_amount' => ...,
            // 'shipping_amount' => ...,
            // 'discount_amount' => ...,
            // 'subtotal_amount' => ...,
            // 'total_amount' => ...,
        ];

        // Use updateOrCreate to handle both inserts and updates
        Cart::updateOrCreate(['id' => $id], $dataToSave);

        return $id;
    }

    public function delete(string $id): void
    {
        Cart::destroy($id);
    }

    public function findByUser(string $userId): array
    {
        return Cart::where('user_id', $userId)
            ->get()
            // Map to array instead of DTO
            ->map(fn(Cart $cart) => [
                'id' => $cart->id,
                'items' => is_string($cart->items) ? json_decode($cart->items, true) : ($cart->items ?? []),
                'user_id' => $cart->user_id,
                'discounts' => is_string($cart->discounts) ? json_decode($cart->discounts, true) : ($cart->discounts ?? []),
                'notes' => is_string($cart->notes) ? json_decode($cart->notes, true) : ($cart->notes ?? []),
                'extra_costs' => is_string($cart->extra_costs) ? json_decode($cart->extra_costs, true) : ($cart->extra_costs ?? []),
                'shipping_method' => $cart->shipping_method,
                'tax_zone' => $cart->tax_zone,
                // 'vat_exempt' => $cart->vat_exempt ?? false,
            ])
            ->toArray();
    }
}
