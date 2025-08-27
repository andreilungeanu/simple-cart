<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\Cart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\Cart\DTOs\ExtraCostDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Represents the state of a shopping cart instance.
 */
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
        $this->items = Collection::make($items)
            ->map(fn ($item) => $item instanceof CartItemDTO ? $item : CartItemDTO::fromArray((array) $item));

        $this->discounts = Collection::make($discounts)
            ->map(fn ($discount) => $discount instanceof DiscountDTO ? $discount : DiscountDTO::fromArray((array) $discount));

        $this->notes = Collection::make($notes);

        $this->extraCosts = Collection::make($extraCosts)
            ->map(fn ($cost) => $cost instanceof ExtraCostDTO ? $cost : ExtraCostDTO::fromArray((array) $cost));
        $this->currentShippingMethod = $shippingMethod;
        $this->vatExempt = $vatExempt;
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
        return $this->items->first(fn (CartItemDTO $item) => $item->id === $itemId);
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
    public function setShippingMethodInternal(?string $method): void
    {
        $this->currentShippingMethod = $method;
    }

    /** @internal */
    public function setShippingVatInfoInternal(?float $rate, bool $included): void
    {
        $this->shippingVatRate = $rate;
        $this->shippingVatIncluded = $included;
    }

    /** @internal */
    public function setVatExemptInternal(bool $exempt): void
    {
        $this->vatExempt = $exempt;
    }
}
