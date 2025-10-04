<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Services;

use AndreiLungeanu\SimpleCart\Data\CartConfiguration;
use AndreiLungeanu\SimpleCart\Enums\CartStatusEnum;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use AndreiLungeanu\SimpleCart\Models\Cart;
use AndreiLungeanu\SimpleCart\Models\CartItem;
use AndreiLungeanu\SimpleCart\Services\Calculators\DiscountCalculator;
use AndreiLungeanu\SimpleCart\Services\Calculators\ShippingCalculator;
use AndreiLungeanu\SimpleCart\Services\Calculators\TaxCalculator;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private CartConfiguration $config,
        private TaxCalculator $taxCalculator,
        private ShippingCalculator $shippingCalculator,
        private DiscountCalculator $discountCalculator,
    ) {}

    public function create(?int $userId = null, ?string $sessionId = null): Cart
    {
        $cart = Cart::create([
            'user_id' => $userId,
            'session_id' => $sessionId ?? session()->getId(),
            'status' => CartStatusEnum::Active,
            'expires_at' => now()->addDays($this->config->ttlDays),
        ]);

        event(new CartUpdated($cart, 'created'));

        return $cart;
    }

    public function find(string $cartId): ?Cart
    {
        return Cart::with('items')->find($cartId);
    }

    public function findOrFail(string $cartId): Cart
    {
        $cart = $this->find($cartId);

        if (! $cart) {
            throw new CartException("Cart with ID {$cartId} not found");
        }

        return $cart;
    }

    /**
     * Find the latest active, non-expired cart for a given user.
     */
    public function findActiveCartForUser(int $userId): ?Cart
    {
        return Cart::with('items')
            ->active()
            ->notExpiredByDate()
            ->forUser($userId)
            ->latest('updated_at')
            ->first();
    }

    /**
     * Find the latest active, non-expired cart for a given session.
     */
    public function findActiveCartForSession(string $sessionId): ?Cart
    {
        return Cart::with('items')
            ->active()
            ->notExpiredByDate()
            ->forSession($sessionId)
            ->latest('updated_at')
            ->first();
    }

    /**
     * Merge a guest (session) cart into the user's cart on login.
     *
     * Rules:
     * - If only a guest cart exists: assign it to the user and refresh TTL.
     * - If only a user cart exists: nothing to do, return it.
     * - If both exist: combine items by product_id (sum quantities),
     *   keep user's item attributes (name/price/category/metadata) when conflict.
     *   Merge discount codes prioritizing user's codes and respecting maxDiscountCodes.
     *   Preserve user's shipping/tax data; otherwise take guest's.
     *   Delete the guest cart afterward.
     *
     * Emits CartUpdated with action 'merged' on the resulting cart.
     */
    public function mergeOnLogin(int $userId, ?string $sessionId = null): ?Cart
    {
        [$userCart, $guestCart] = $this->resolveActiveCarts($userId, $sessionId);

        if (! $guestCart && ! $userCart) {
            return null; // No carts to merge
        }

        if ($guestCart && ! $userCart) {
            return $this->claimGuestCart($guestCart, $userId);
        }

        if ($userCart && ! $guestCart) {
            return $userCart;
        }

        // Safety guard and self-merge avoidance
        if (! $userCart || ! $guestCart || $userCart->id === $guestCart->id) {
            return $userCart ?? $guestCart;
        }

        return match (strtolower($this->config->onLoginCartStrategy)) {
            'user' => $this->keepUserCart($userCart, $guestCart),
            'guest' => $this->keepGuestCart($userCart, $guestCart, $userId),
            default => $this->mergeGuestIntoUser($userCart, $guestCart),
        };
    }

    private function resolveActiveCarts(int $userId, ?string $sessionId): array
    {
        $userCart = $this->findActiveCartForUser($userId);
        $guestCart = $sessionId ? $this->findActiveCartForSession($sessionId) : null;

        return [$userCart, $guestCart];
    }

    private function claimGuestCart(Cart $guestCart, int $userId): Cart
    {
        $guestCart->update([
            'user_id' => $userId,
            'expires_at' => now()->addDays($this->config->ttlDays),
        ]);

        event(new CartUpdated($guestCart->fresh(['items']), 'merged', [
            'from_cart_id' => $guestCart->id,
            'strategy' => 'claimed_guest',
        ]));

        return $guestCart;
    }

    private function keepUserCart(Cart $userCart, Cart $guestCart): Cart
    {
        $fromCartId = $guestCart->id;
        $guestCart->delete();

        event(new CartUpdated($userCart->fresh(['items']), 'merged', [
            'from_cart_id' => $fromCartId,
            'strategy' => 'keep_user',
        ]));

        return $userCart;
    }

    private function keepGuestCart(Cart $userCart, Cart $guestCart, int $userId): Cart
    {
        $fromCartId = $userCart->id;

        DB::transaction(function () use ($userCart, $guestCart, $userId) {
            $guestCart->update([
                'user_id' => $userId,
                'expires_at' => now()->addDays($this->config->ttlDays),
            ]);
            $userCart->delete();
        });

        $claimed = $guestCart->fresh(['items']);
        event(new CartUpdated($claimed, 'merged', [
            'from_cart_id' => $fromCartId,
            'strategy' => 'keep_guest',
        ]));

        return $claimed;
    }

    private function mergeGuestIntoUser(Cart $userCart, Cart $guestCart): Cart
    {
        $result = DB::transaction(function () use ($userCart, $guestCart) {
            $userCart->loadMissing('items');
            $guestCart->loadMissing('items');

            $guestItems = $guestCart->items;
            $userItemsByProduct = $userCart->items->keyBy('product_id');

            foreach ($guestItems as $gItem) {
                $uItem = $userItemsByProduct->get($gItem->product_id);
                if ($uItem) {
                    $uItem->update(['quantity' => $uItem->quantity + $gItem->quantity]);
                } else {
                    CartItem::create([
                        'cart_id' => $userCart->id,
                        'product_id' => $gItem->product_id,
                        'name' => $gItem->name,
                        'price' => (float) $gItem->price,
                        'quantity' => $gItem->quantity,
                        'category' => $gItem->category,
                        'metadata' => $gItem->metadata ?? [],
                    ]);
                }
            }

            // Discounts: keep user first, append guest up to cap
            $mergedDiscounts = $userCart->discount_data ?? [];
            $guestDiscounts = $guestCart->discount_data ?? [];
            foreach ($guestDiscounts as $code => $data) {
                if (! isset($mergedDiscounts[$code]) && count($mergedDiscounts) < $this->config->maxDiscountCodes) {
                    $mergedDiscounts[$code] = $data;
                }
            }

            $shipping = $userCart->shipping_data ?? $guestCart->shipping_data;
            $tax = $userCart->tax_data ?? $guestCart->tax_data;

            $userCart->update([
                'discount_data' => $mergedDiscounts ?: [],
                'shipping_data' => $shipping,
                'tax_data' => $tax,
                'expires_at' => now()->addDays($this->config->ttlDays),
            ]);

            $fromCartId = $guestCart->id;
            $guestCart->delete();

            return [$userCart->fresh(['items']), $fromCartId];
        });

        [$finalCart, $fromCartId] = $result;

        event(new CartUpdated($finalCart, 'merged', [
            'from_cart_id' => $fromCartId,
            'strategy' => 'guest_into_user',
        ]));

        return $finalCart;
    }

    public function addItem(Cart $cart, array $itemData): CartItem
    {
        $this->validateItemData($itemData);

        $existingItem = $cart->items()->where('product_id', $itemData['product_id'])->first();
        $newQuantity = ($existingItem->quantity ?? 0) + ($itemData['quantity'] ?? 1);

        /** @var CartItem $item */
        $item = $cart->items()->updateOrCreate(
            ['product_id' => $itemData['product_id']],
            [
                'name' => $itemData['name'],
                'price' => $itemData['price'],
                'quantity' => $newQuantity,
                'category' => $itemData['category'] ?? null,
                'metadata' => $itemData['metadata'] ?? [],
            ]
        );

        event(new CartUpdated($cart->fresh(['items']), 'item_added', ['item' => $item]));

        return $item;
    }

    public function updateQuantity(Cart $cart, string $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($cart, $productId);

            return;
        }

        $updated = $cart->items()->where('product_id', $productId)->update(['quantity' => $quantity]);

        if ($updated) {
            event(new CartUpdated($cart->fresh(['items']), 'item_updated', ['product_id' => $productId]));
        }
    }

    public function removeItem(Cart $cart, string $productId): void
    {
        $deleted = $cart->items()->where('product_id', $productId)->delete();

        if ($deleted) {
            event(new CartUpdated($cart->fresh(['items']), 'item_removed', ['product_id' => $productId]));
        }
    }

    public function calculateSubtotal(Cart $cart): float
    {
        return $cart->subtotal;
    }

    public function calculateShipping(Cart $cart): float
    {
        $appliedDiscounts = $this->getAppliedDiscounts($cart);

        return $this->shippingCalculator->calculate($cart, $appliedDiscounts);
    }

    public function isFreeShippingApplied(Cart $cart): bool
    {
        $appliedDiscounts = $this->getAppliedDiscounts($cart);

        return $this->shippingCalculator->isFreeShippingApplied($cart, $appliedDiscounts);
    }

    public function calculateTax(Cart $cart): float
    {
        $subtotal = $this->calculateSubtotal($cart);
        $shipping = $this->calculateShipping($cart);

        return $this->taxCalculator->calculate($cart, $subtotal, $shipping);
    }

    public function calculateTotal(Cart $cart): float
    {
        $subtotal = $this->calculateSubtotal($cart);
        $shipping = $this->calculateShipping($cart);
        $tax = $this->calculateTax($cart);
        $discounts = $this->calculateDiscounts($cart);

        return round($subtotal + $shipping + $tax - $discounts, 2);
    }

    public function applyDiscount(Cart $cart, array $discountData): void
    {
        // Validate required discount data structure
        if (! isset($discountData['code'], $discountData['type'], $discountData['value'])) {
            throw new CartException('Discount data must include code, type, and value');
        }

        $discounts = $cart->discount_data ?? [];

        if (isset($discounts[$discountData['code']])) {
            return; // Discount already applied
        }

        if (count($discounts) >= $this->config->maxDiscountCodes) {
            throw new CartException("Cannot apply more than {$this->config->maxDiscountCodes} discount codes");
        }

        // Store the full discount data indexed by code
        $discounts[$discountData['code']] = $discountData;
        $cart->update(['discount_data' => $discounts]);

        event(new CartUpdated($cart, 'discount_applied', ['code' => $discountData['code']]));
    }

    public function removeDiscount(Cart $cart, string $code): void
    {
        $discounts = $cart->discount_data ?? [];

        if (isset($discounts[$code])) {
            unset($discounts[$code]);
            $cart->update(['discount_data' => $discounts]);
            event(new CartUpdated($cart, 'discount_removed', ['code' => $code]));
        }
    }

    public function getAppliedDiscounts(Cart $cart): array
    {
        return $cart->discount_data ?? [];
    }

    public function applyShipping(Cart $cart, array $shippingData): void
    {
        // Validate required shipping data structure
        if (! isset($shippingData['method_name'], $shippingData['cost'])) {
            throw new CartException('Shipping data must include method_name and cost');
        }

        // Ensure cost is numeric
        if (! is_numeric($shippingData['cost']) || $shippingData['cost'] < 0) {
            throw new CartException('Shipping cost must be a non-negative number');
        }

        $cart->update(['shipping_data' => $shippingData]);
        event(new CartUpdated($cart, 'shipping_applied', ['method' => $shippingData['method_name']]));
    }

    public function removeShipping(Cart $cart): void
    {
        $cart->update(['shipping_data' => null]);
        event(new CartUpdated($cart, 'shipping_removed', []));
    }

    public function getAppliedShipping(Cart $cart): ?array
    {
        return $cart->shipping_data;
    }

    public function applyTax(Cart $cart, array $taxData): void
    {
        $this->validateTaxData($taxData);

        $cart->update(['tax_data' => $taxData]);
        event(new CartUpdated($cart, 'tax_applied', ['tax_data' => $taxData]));
    }

    public function removeTax(Cart $cart): void
    {
        $cart->update(['tax_data' => null]);
        event(new CartUpdated($cart, 'tax_removed'));
    }

    public function getAppliedTax(Cart $cart): ?array
    {
        return $cart->tax_data;
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update([
            'discount_data' => [],
            'shipping_data' => null,
            'tax_data' => null,
        ]);

        event(new CartUpdated($cart, 'cleared'));
    }

    public function delete(Cart $cart): void
    {
        $cartId = $cart->id;
        $cart->delete();

        event(new CartUpdated($cart, 'deleted', ['cart_id' => $cartId]));
    }

    public function getCartSummary(Cart $cart): array
    {
        return [
            'id' => $cart->id,
            'item_count' => $cart->item_count,
            'subtotal' => $this->calculateSubtotal($cart),
            'shipping' => $this->calculateShipping($cart),
            'tax' => $this->calculateTax($cart),
            'discounts' => $this->calculateDiscounts($cart),
            'total' => $this->calculateTotal($cart),
            'status' => $cart->status->value,
            'expires_at' => $cart->expires_at?->toISOString(),
        ];
    }

    private function calculateDiscounts(Cart $cart): float
    {
        $subtotal = $this->calculateSubtotal($cart);

        return $this->discountCalculator->calculate($cart, $subtotal);
    }

    private function validateItemData(array $itemData): void
    {
        $required = ['product_id', 'name', 'price'];

        foreach ($required as $field) {
            if (! isset($itemData[$field])) {
                throw new CartException("Missing required field: {$field}");
            }
        }

        if (! is_numeric($itemData['price'])) {
            throw new CartException('Price must be a number');
        }

        if ((float) $itemData['price'] < 0) {
            throw new CartException('Price cannot be negative');
        }

        if (isset($itemData['quantity']) && $itemData['quantity'] < 1) {
            throw new CartException('Quantity must be at least 1');
        }
    }

    private function validateTaxData(array $taxData): void
    {
        if (! isset($taxData['rate'])) {
            throw new CartException('Tax data must include a rate');
        }

        if (! is_numeric($taxData['rate']) || $taxData['rate'] < 0 || $taxData['rate'] > 1) {
            throw new CartException('Tax rate must be a number between 0 and 1');
        }

        // Validate shipping rate if provided
        if (isset($taxData['shipping_rate'])) {
            if (! is_numeric($taxData['shipping_rate']) || $taxData['shipping_rate'] < 0 || $taxData['shipping_rate'] > 1) {
                throw new CartException('Shipping tax rate must be a number between 0 and 1');
            }
        }

        // Validate condition rates if provided
        if (isset($taxData['conditions'])) {
            $this->validateTaxConditionRates($taxData['conditions']);
        }
    }

    private function validateTaxConditionRates(array $conditions): void
    {
        $rateFields = ['rates_per_item', 'rates_per_category', 'rates_per_type'];

        foreach ($rateFields as $field) {
            if (isset($conditions[$field]) && is_array($conditions[$field])) {
                foreach ($conditions[$field] as $key => $rate) {
                    if (! is_numeric($rate) || $rate < 0 || $rate > 1) {
                        throw new CartException("Invalid tax rate for {$field}[{$key}]: must be between 0 and 1");
                    }
                }
            }
        }
    }
}
