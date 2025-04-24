<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider; // Keep for now, might be needed for internal calculations or removed later
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Events\CartCleared; // Events might be dispatched by Manager now
use AndreiLungeanu\SimpleCart\Events\CartCreated; // Events might be dispatched by Manager now
use AndreiLungeanu\SimpleCart\Events\CartUpdated; // Events might be dispatched by Manager now
use AndreiLungeanu\SimpleCart\Exceptions\CartException; // Keep only one
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
// Removed duplicate DTO/Exception imports below

// Renamed from SimpleCart
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

    // Constructor simplified - removed Repository, Calculator, Action injections
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

    // --- Methods below will be refactored ---
    // create, addItem, removeItem, clear, get, updateQuantity, applyDiscount, addNote
    // save, find (these will move primarily to Manager/Repository)
    // total, clone, merge, addExtraCost, setShippingMethod, setVatExempt
    // getSubtotal, getItemCount, getShippingAmount, getTaxAmount, getDiscountAmount, getExtraCostsTotal
    // getShippingMethod, getShippingVatInfo, isVatExempt
    // getItems, getDiscounts, getNotes, getExtraCosts
    // findItem (internal helper, might stay)

    // --- Keeping existing methods for now, will refactor in subsequent steps ---

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

        // Event dispatching should move to the Manager
        // event(new CartCreated($this));

        return $this;
    }

    /**
     * Add an item to the cart. Accepts a DTO instance or an associative array.
     * Logic might move to Action, called by Manager.
     *
     * @param CartItemDTO|array $item
     * @return static
     * @throws \InvalidArgumentException
     */
    public function addItem(CartItemDTO|array $item): static
    {
        $itemDTO = $item instanceof CartItemDTO ? $item : CartItemDTO::fromArray($item);

        // Action execution should happen in the Manager
        // ($this->addItemAction)($this, $itemDTO);
        // For now, just add directly to demonstrate state change (will be refined)
        $existingItem = $this->findItem($itemDTO->id);
        if ($existingItem) {
            $this->updateQuantity($itemDTO->id, $existingItem->quantity + $itemDTO->quantity);
        } else {
            $this->items->push($itemDTO);
        }
        // Event dispatching should move to the Manager
        // event(new CartUpdated($this));
        return $this;
    }

    public function removeItem(string $itemId): static
    {
        $initialCount = $this->items->count();
        $this->items = $this->items->filter(fn(CartItemDTO $item) => $item->id !== $itemId);

        // Event dispatching should move to the Manager
        // if ($this->items->count() < $initialCount) {
        //     event(new CartUpdated($this));
        // }

        return $this;
    }

    public function clear(): static
    {
        // Keep internal state clearing, but ID/User/TaxZone might be preserved depending on Manager logic
        // $this->id = (string) Str::uuid(); // Manager might decide if ID changes
        // $this->userId = null;
        // $this->taxZone = null;
        $this->items = collect([]);
        $this->discounts = collect([]);
        $this->notes = collect([]);
        $this->extraCosts = collect([]);
        $this->shippingVatRate = null;
        $this->shippingVatIncluded = false;
        $this->vatExempt = false;
        $this->currentShippingMethod = null;

        // Event dispatching should move to the Manager
        // event(new CartCleared($this->id));

        return $this;
    }

    // This method might become redundant or simplified if Manager controls data retrieval format
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
            // Consider removing item instead? Or let Manager decide?
            // For now, keep exception, but could change.
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $updated = false;
        $this->items = $this->items->map(function ($item) use ($itemId, $quantity, &$updated) {
            if ($item->id === $itemId) {
                $updated = true;
                // Assuming CartItemDTO has a withQuantity method or similar immutable update
                return $item instanceof CartItemDTO ? $item->withQuantity($quantity) : $item;
            }
            return $item;
        });

        if (! $updated) {
            throw new CartException("Item with ID {$itemId} not found in cart.");
        }

        // Event dispatching should move to the Manager
        // event(new CartUpdated($this));

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
        // Event dispatching should move to the Manager
        // event(new CartUpdated($this));

        return $this;
    }

    public function addNote(string $note): static
    {
        $this->notes->push($note);
        // Event dispatching should move to the Manager
        // event(new CartUpdated($this));
        return $this;
    }

    // save() method will be removed - Manager/Repository handles this
    /*
    public function save(): static
    {
        // ... logic removed ...
    }
    */

    // find() method will be removed - Manager/Repository handles this
    /*
    public function find(string $id): static
    {
        // ... logic removed ...
    }
    */

    // total() and other calculation methods will be removed - Manager calls Calculator
    /*
    public function total(): float
    {
        // return $this->calculator->getTotal($this); // Manager will do this
    }
    */

    // clone() might still be useful for the Manager, but implementation needs review
    public function clone(): static
    {
        // Cloning dependencies is wrong here, constructor needs simplification first
        $clonedCart = new self(
            // Dependencies removed from constructor call
            id: (string) Str::uuid(), // New ID for clone
            userId: $this->userId,
            taxZone: $this->taxZone,
            items: $this->items->map(fn(CartItemDTO $item) => clone $item)->all(),
            discounts: $this->discounts->map(fn(DiscountDTO $discount) => clone $discount)->all(),
            notes: $this->notes->all(), // Notes are strings, no clone needed
            extraCosts: $this->extraCosts->map(fn(ExtraCostDTO $cost) => clone $cost)->all(),
            shippingMethod: $this->currentShippingMethod,
            vatExempt: $this->vatExempt
        );

        // This part that copies state back seems redundant if constructor does its job?
        // $this->id = $clonedCart->id;
        // ... etc ...
        // Let's simplify: just return the new instance created by constructor
        return $clonedCart;
    }

    // merge() logic seems okay for combining state, Manager would orchestrate
    public function merge(self $otherCart): static // Note: type hint changed to self
    {
        foreach ($otherCart->getItems() as $item) {
            // Need logic to handle duplicates (update quantity or add as new line?) - using addItem logic for now
            $this->addItem(clone $item); // Use addItem to handle existing checks/quantity updates
            // if ($item instanceof CartItemDTO) {
            //     $this->items->push(clone $item);
            // }
        }

        foreach ($otherCart->getDiscounts() as $discount) {
            // Check for duplicates? For now, just add.
            if ($discount instanceof DiscountDTO) {
                $this->discounts->push(clone $discount);
            }
        }

        // Merge notes, extra costs? Add as needed.
        foreach ($otherCart->getNotes() as $note) {
            $this->notes->push($note);
        }
        foreach ($otherCart->getExtraCosts() as $cost) {
            if ($cost instanceof ExtraCostDTO) {
                $this->extraCosts->push(clone $cost);
            }
        }


        // Event dispatching should move to the Manager
        // event(new CartUpdated($this));

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
        // Event dispatching should move to the Manager
        // event(new CartUpdated($this));
        return $this;
    }

    public function setShippingMethod(string $method, array $shippingInfo): static
    {
        // Validation can stay here or move to Manager
        if (array_key_exists('vat_rate', $shippingInfo) && is_numeric($shippingInfo['vat_rate'])) {
            if ($shippingInfo['vat_rate'] < 0 || $shippingInfo['vat_rate'] > 1) {
                throw new \InvalidArgumentException('VAT rate must be between 0 and 1');
            }
        }

        $this->currentShippingMethod = $method;
        $this->shippingVatRate = $shippingInfo['vat_rate'] ?? null;
        $this->shippingVatIncluded = $shippingInfo['vat_included'] ?? false;
        // Event dispatching should move to the Manager
        // event(new CartUpdated($this));
        return $this;
    }

    public function setVatExempt(bool $exempt = true): static
    {
        $this->vatExempt = $exempt;
        // Event dispatching should move to the Manager
        // event(new CartUpdated($this));
        return $this;
    }

    // Calculation getters will be removed - Manager calls Calculator
    /*
    public function getSubtotal(): float { ... }
    public function getItemCount(): int { ... }
    public function getShippingAmount(): float { ... }
    public function getTaxAmount(): float { ... }
    public function getDiscountAmount(): float { ... }
    public function getExtraCostsTotal(): float { ... }
    */

    // Simple getters for state properties are fine
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

    // Internal helper method - made public for Action usage
    public function findItem(string $itemId): ?CartItemDTO
    {
        return $this->items->first(fn(CartItemDTO $item) => $item->id === $itemId);
    }

    // --- Methods for internal state modification (used by other methods in this class) ---
    // These might be called directly by the Manager or Actions in the future

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

    /**
     * @internal Used by the repository to set loaded shipping VAT info.
     */
    public function setShippingVatInfoInternal(?float $rate, bool $included): void
    {
        $this->shippingVatRate = $rate;
        $this->shippingVatIncluded = $included;
    }
}
