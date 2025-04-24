<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Events\CartCleared;
use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CartInstance
{
    public string $id = '';
    public ?string $userId = null;
    public ?string $taxZone = null;
    protected Collection $items;
    protected Collection $discounts;
    protected Collection $notes;
    protected Collection $extraCosts;
    private ?float $shippingVatRate = null;
    private bool $shippingVatIncluded = false;
    private bool $vatExempt = false;
    protected ?string $currentShippingMethod = null;

    public function __construct(
        string $id = '',
        ?string $userId = null,
        ?string $taxZone = null,
        array $items = [],
        array $discounts = [],
        array $notes = [],
        array $extraCosts = [],
        ?string $shippingMethod = null,
        bool $vatExempt = false,
    ) {
        $this->id = $id ?: (string) Str::uuid();
        $this->userId = $userId;
        $this->taxZone = $taxZone;
        $this->items = collect($items)->map(fn($item) => $item instanceof CartItemDTO ? $item : new CartItemDTO(...$item));
        $this->discounts = collect($discounts)->map(fn($discount) => $discount instanceof DiscountDTO ? $discount : new DiscountDTO(...$discount));
        $this->notes = collect($notes);
        $this->extraCosts = collect($extraCosts)->map(fn($cost) => $cost instanceof ExtraCostDTO ? $cost : new ExtraCostDTO(...$cost));
        $this->currentShippingMethod = $shippingMethod;
        $this->vatExempt = $vatExempt;
    }

    public function create(string $id = '', ?string $userId = null, ?string $taxZone = null): static
    {
        $this->id = $id ?: (string) Str::uuid();
        $this->userId = $userId;
        $this->taxZone = $taxZone;
        $this->items = collect([]);
        $this->discounts = collect([]);
        $this->notes = collect([]);
        $this->extraCosts = collect([]);
        $this->shippingVatRate = null;
        $this->shippingVatIncluded = false;
        $this->vatExempt = false;
        $this->currentShippingMethod = null;

        return $this;
    }

    /**
     * Add an item to the cart. Accepts a DTO instance or an associative array.
     *
     * @param CartItemDTO|array $item
     * @return static
     * @throws \InvalidArgumentException
     */
    public function addItem(CartItemDTO|array $item): static
    {
        $itemDTO = $item instanceof CartItemDTO ? $item : CartItemDTO::fromArray($item);

        $existingItem = $this->findItem($itemDTO->id);
        if ($existingItem) {
            $this->updateQuantity($itemDTO->id, $existingItem->quantity + $itemDTO->quantity);
        } else {
            $this->items->push($itemDTO);
        }
        return $this;
    }

    public function removeItem(string $itemId): static
    {
        $this->items = $this->items->filter(fn(CartItemDTO $item) => $item->id !== $itemId);

        return $this;
    }

    public function clear(): static
    {
        $this->items = collect([]);
        $this->discounts = collect([]);
        $this->notes = collect([]);
        $this->extraCosts = collect([]);
        $this->shippingVatRate = null;
        $this->shippingVatIncluded = false;
        $this->vatExempt = false;
        $this->currentShippingMethod = null;

        return $this;
    }

    public function get(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'tax_zone' => $this->taxZone,
            'items' => $this->items->map(fn(CartItemDTO $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'category' => $item->category,
                'metadata' => $item->metadata,
            ])->values()->toArray(),
            'discounts' => $this->discounts->map(fn(DiscountDTO $discount) => [
                'code' => $discount->code,
                'type' => $discount->type,
                'value' => $discount->value,
                'appliesTo' => $discount->appliesTo,
                'minimumAmount' => $discount->minimumAmount,
                'expiresAt' => $discount->expiresAt,
            ])->toArray(),
            'notes' => $this->notes->toArray(),
            'extra_costs' => $this->extraCosts->map(fn(ExtraCostDTO $cost) => [
                'name' => $cost->name,
                'amount' => $cost->amount,
                'type' => $cost->type,
                'description' => $cost->description,
                'vatRate' => $cost->vatRate,
                'vatIncluded' => $cost->vatIncluded,
            ])->toArray(),
            'shipping_method' => $this->currentShippingMethod,
            'vat_exempt' => $this->vatExempt,
        ];
    }

    public function updateQuantity(string $itemId, int $quantity): static
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $updated = false;
        $this->items = $this->items->map(function ($item) use ($itemId, $quantity, &$updated) {
            if ($item->id === $itemId) {
                $updated = true;
                return $item instanceof CartItemDTO ? $item->withQuantity($quantity) : $item;
            }
            return $item;
        });

        if (! $updated) {
            throw new CartException("Item with ID {$itemId} not found in cart.");
        }

        return $this;
    }

    /**
     * Apply a discount to the cart. Accepts a DTO instance or an associative array.
     *
     * @param DiscountDTO|array $discount
     * @return static
     * @throws \InvalidArgumentException
     */
    public function applyDiscount(DiscountDTO|array $discount): static
    {
        $discountDTO = $discount instanceof DiscountDTO ? $discount : DiscountDTO::fromArray($discount);

        $this->discounts->push($discountDTO);
        return $this;
    }

    public function addNote(string $note): static
    {
        $this->notes->push($note);
        return $this;
    }

    public function clone(): self
    {
        $clonedCart = new self(
            id: (string) Str::uuid(),
            userId: $this->userId,
            taxZone: $this->taxZone,
            items: $this->items->map(fn(CartItemDTO $item) => clone $item)->all(),
            discounts: $this->discounts->map(fn(DiscountDTO $discount) => clone $discount)->all(),
            notes: $this->notes->all(),
            extraCosts: $this->extraCosts->map(fn(ExtraCostDTO $cost) => clone $cost)->all(),
            shippingMethod: $this->currentShippingMethod,
            vatExempt: $this->vatExempt
        );

        return $clonedCart;
    }

    public function merge(self $otherCart): static
    {
        foreach ($otherCart->getItems() as $item) {
            $this->addItem(clone $item);
        }

        foreach ($otherCart->getDiscounts() as $discount) {
            if ($discount instanceof DiscountDTO) {
                $this->discounts->push(clone $discount);
            }
        }

        foreach ($otherCart->getNotes() as $note) {
            $this->notes->push($note);
        }
        foreach ($otherCart->getExtraCosts() as $cost) {
            if ($cost instanceof ExtraCostDTO) {
                $this->extraCosts->push(clone $cost);
            }
        }

        return $this;
    }

    /**
     * Add an extra cost to the cart. Accepts a DTO instance or an associative array.
     *
     * @param ExtraCostDTO|array $cost
     * @return static
     * @throws \InvalidArgumentException
     */
    public function addExtraCost(ExtraCostDTO|array $cost): static
    {
        $extraCostDTO = $cost instanceof ExtraCostDTO ? $cost : ExtraCostDTO::fromArray($cost);

        $this->extraCosts->push($extraCostDTO);
        return $this;
    }

    /**
     * Remove an extra cost by its name.
     *
     * @param string $name The name of the extra cost to remove.
     * @return static
     */
    public function removeExtraCost(string $name): static
    {
        $this->extraCosts = $this->extraCosts->filter(fn(ExtraCostDTO $cost) => $cost->name !== $name);
        return $this;
    }

    public function setShippingMethod(string $method, array $shippingInfo): static
    {
        if (array_key_exists('vat_rate', $shippingInfo) && is_numeric($shippingInfo['vat_rate'])) {
            if ($shippingInfo['vat_rate'] < 0 || $shippingInfo['vat_rate'] > 1) {
                throw new \InvalidArgumentException('VAT rate must be between 0 and 1');
            }
        }

        $this->currentShippingMethod = $method;
        $this->shippingVatRate = $shippingInfo['vat_rate'] ?? null;
        $this->shippingVatIncluded = $shippingInfo['vat_included'] ?? false;
        return $this;
    }

    public function setVatExempt(bool $exempt = true): static
    {
        $this->vatExempt = $exempt;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getTaxZone(): ?string
    {
        return $this->taxZone;
    }

    public function getShippingMethod(): ?string
    {
        return $this->currentShippingMethod;
    }

    public function getShippingVatInfo(): array
    {
        return [
            'rate' => $this->shippingVatRate,
            'included' => $this->shippingVatIncluded,
        ];
    }

    public function isVatExempt(): bool
    {
        return $this->vatExempt;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getDiscounts(): Collection
    {
        return $this->discounts;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function getExtraCosts(): Collection
    {
        return $this->extraCosts;
    }

    public function findItem(string $itemId): ?CartItemDTO
    {
        return $this->items->first(fn(CartItemDTO $item) => $item->id === $itemId);
    }

    /** @internal */
    public function setItems(Collection $items): void
    {
        $this->items = $items;
    }

    /** @internal */
    public function setDiscounts(Collection $discounts): void
    {
        $this->discounts = $discounts;
    }

    /** @internal */
    public function setNotes(Collection $notes): void
    {
        $this->notes = $notes;
    }

    /** @internal */
    public function setExtraCosts(Collection $extraCosts): void
    {
        $this->extraCosts = $extraCosts;
    }

    /** @internal */
    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    /** @internal */
    public function setTaxZone(?string $taxZone): void
    {
        $this->taxZone = $taxZone;
    }

    /** @internal */
    public function setShippingVatInfoInternal(?float $rate, bool $included): void
    {
        $this->shippingVatRate = $rate;
        $this->shippingVatIncluded = $included;
    }
}
