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
use AndreiLungeanu\SimpleCart\Repositories\CartRepository;
use AndreiLungeanu\SimpleCart\Services\CartCalculator;
use AndreiLungeanu\SimpleCart\Actions\AddItemToCartAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SimpleCart
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
        protected readonly CartRepository $repository,
        protected readonly CartCalculator $calculator,
        protected readonly AddItemToCartAction $addItemAction,
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

        event(new CartCreated($this));

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

        $this->addItemAction->execute($this, $itemDTO);
        return $this;
    }

    public function removeItem(string $itemId): static
    {
        $initialCount = $this->items->count();
        $this->items = $this->items->filter(fn(CartItemDTO $item) => $item->id !== $itemId);

        if ($this->items->count() < $initialCount) {
            event(new CartUpdated($this));
        }

        return $this;
    }

    public function clear(): static
    {
        $this->id = (string) Str::uuid();
        $this->userId = null;
        $this->taxZone = null;
        $this->items = collect([]);
        $this->discounts = collect([]);
        $this->notes = collect([]);
        $this->extraCosts = collect([]);
        $this->shippingVatRate = null;
        $this->shippingVatIncluded = false;
        $this->vatExempt = false;
        $this->currentShippingMethod = null;

        event(new CartCleared($this->id));

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
                return $item->withQuantity($quantity);
            }
            return $item;
        });

        if (! $updated) {
            throw new CartException("Item with ID {$itemId} not found in cart.");
        }

        event(new CartUpdated($this));

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
        event(new CartUpdated($this));

        return $this;
    }

    public function addNote(string $note): static
    {
        $this->notes->push($note);
        event(new CartUpdated($this));

        return $this;
    }

    public function save(): static
    {
        if (! $this->id) {
            $this->id = (string) Str::uuid();
        }

        $cartData = [
            'id' => $this->id,
            'items' => $this->items->map(fn(CartItemDTO $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'category' => $item->category,
                'metadata' => $item->metadata,
            ])->toArray(),
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
            'user_id' => $this->userId,
            'shipping_method' => $this->currentShippingMethod,
            'tax_zone' => $this->taxZone,
            'vat_exempt' => $this->vatExempt,
        ];

        $savedId = $this->repository->save($cartData);
        $this->id = $savedId;

        return $this;
    }

    public function find(string $id): static
    {
        $foundCartData = $this->repository->find($id);

        if (! $foundCartData) {
            $this->create();
            $this->id = $id;
            throw new CartException("Cart with ID {$id} not found.");
        }

        $this->id = $foundCartData['id'];
        $this->userId = $foundCartData['user_id'] ?? null;
        $this->taxZone = $foundCartData['tax_zone'] ?? null;
        $this->currentShippingMethod = $foundCartData['shipping_method'] ?? null;
        $this->vatExempt = $foundCartData['vat_exempt'] ?? false;

        $this->items = collect($foundCartData['items'] ?? [])->map(fn($item) => $item instanceof CartItemDTO ? $item : new CartItemDTO(...$item));
        $this->discounts = collect($foundCartData['discounts'] ?? [])->map(fn($discount) => $discount instanceof DiscountDTO ? $discount : new DiscountDTO(...$discount));
        $this->notes = collect($foundCartData['notes'] ?? []);
        $this->extraCosts = collect($foundCartData['extra_costs'] ?? [])->map(fn($cost) => $cost instanceof ExtraCostDTO ? $cost : new ExtraCostDTO(...$cost));

        $this->shippingVatRate = null;
        $this->shippingVatIncluded = false;

        return $this;
    }

    public function total(): float
    {
        return $this->calculator->getTotal($this);
    }

    public function clone(): static
    {
        $clonedCart = new self(
            repository: $this->repository,
            calculator: $this->calculator,
            addItemAction: $this->addItemAction,
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

        $this->id = $clonedCart->id;
        $this->userId = $clonedCart->userId;
        $this->taxZone = $clonedCart->taxZone;
        $this->items = $clonedCart->getItems();
        $this->discounts = $clonedCart->getDiscounts();
        $this->notes = $clonedCart->getNotes();
        $this->extraCosts = $clonedCart->getExtraCosts();
        $this->currentShippingMethod = $clonedCart->currentShippingMethod;
        $this->vatExempt = $clonedCart->vatExempt;
        $this->shippingVatRate = $clonedCart->shippingVatRate;
        $this->shippingVatIncluded = $clonedCart->shippingVatIncluded;

        return $this;
    }

    public function merge(SimpleCart $otherCart): static
    {
        foreach ($otherCart->getItems() as $item) {
            if ($item instanceof CartItemDTO) {
                $this->items->push(clone $item);
            }
        }

        foreach ($otherCart->getDiscounts() as $discount) {
            if ($discount instanceof DiscountDTO) {
                $this->discounts->push(clone $discount);
            }
        }

        event(new CartUpdated($this));

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
        event(new CartUpdated($this));
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
        event(new CartUpdated($this));
        return $this;
    }

    public function setVatExempt(bool $exempt = true): static
    {
        $this->vatExempt = $exempt;
        event(new CartUpdated($this));
        return $this;
    }

    public function getSubtotal(): float
    {
        return $this->calculator->getSubtotal($this);
    }

    public function getItemCount(): int
    {
        return $this->calculator->getItemCount($this);
    }

    public function getShippingAmount(): float
    {
        return $this->calculator->getShippingAmount($this);
    }

    public function getTaxAmount(): float
    {
        return $this->calculator->getTaxAmount($this);
    }

    public function getDiscountAmount(): float
    {
        return $this->calculator->getDiscountAmount($this);
    }

    public function getExtraCostsTotal(): float
    {
        return $this->calculator->getExtraCostsTotal($this);
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

    protected function findItem(string $itemId): ?CartItemDTO
    {
        return $this->items->first(fn($item) => $item->id === $itemId);
    }
}
