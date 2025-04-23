<?php

namespace AndreiLungeanu\SimpleCart;

// Remove CartDTO import - no longer needed here
// use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Events\CartCleared;
use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use AndreiLungeanu\SimpleCart\Repositories\CartRepository;
use AndreiLungeanu\SimpleCart\Services\CartCalculator; // Import CartCalculator
use AndreiLungeanu\SimpleCart\Actions\AddItemToCartAction; // Import Action
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SimpleCart
{
    // Properties moved from CartDTO
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
    // End properties moved from CartDTO

    // Inject CartCalculator and Actions
    public function __construct(
        protected readonly CartRepository $repository,
        protected readonly CartCalculator $calculator,
        protected readonly AddItemToCartAction $addItemAction, // Inject Action
        // Initialize properties in constructor
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
        $this->id = $id ?: (string) Str::uuid(); // Ensure ID is set
        $this->userId = $userId;
        $this->taxZone = $taxZone;
        $this->items = collect($items)->map(fn($item) => $item instanceof CartItemDTO ? $item : new CartItemDTO(...$item));
        $this->discounts = collect($discounts)->map(fn($discount) => $discount instanceof DiscountDTO ? $discount : new DiscountDTO(...$discount)); // Assuming DiscountDTO constructor takes array
        $this->notes = collect($notes);
        $this->extraCosts = collect($extraCosts)->map(fn($cost) => $cost instanceof ExtraCostDTO ? $cost : new ExtraCostDTO(...$cost)); // Assuming ExtraCostDTO constructor takes array
        $this->currentShippingMethod = $shippingMethod;
        $this->vatExempt = $vatExempt;
        // Initialize collections if empty arrays passed
        if ($this->items === null) $this->items = collect([]);
        if ($this->discounts === null) $this->discounts = collect([]);
        if ($this->notes === null) $this->notes = collect([]);
        if ($this->extraCosts === null) $this->extraCosts = collect([]);
    }

    public function create(string $id = '', ?string $userId = null, ?string $taxZone = null): static
    {
        // Reset properties to initial state
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

        event(new CartCreated($this)); // Pass $this instead of $this->cart

        return $this;
    }

    // Delegate to AddItemToCartAction
    public function addItem(CartItemDTO $item): static
    {
        $this->addItemAction->execute($this, $item);
        return $this; // Action returns $this, but keep fluent chain here
    }

    public function removeItem(string $itemId): static
    {
        $initialCount = $this->items->count();
        // Filter the collection, keeping only items whose ID does *not* match
        $this->items = $this->items->filter(fn(CartItemDTO $item) => $item->id !== $itemId);

        // Only dispatch event if an item was actually removed
        if ($this->items->count() < $initialCount) {
            event(new CartUpdated($this));
        }

        return $this;
    }

    public function clear(): static
    {
        // Reset properties similar to create but keep repository
        $this->id = (string) Str::uuid(); // Generate new ID? Or keep old one? Let's generate new for now.
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

        event(new CartCleared($this->id)); // Pass ID or $this? Let's pass ID for now.

        return $this;
    }

    // Changed to return array representation of the cart
    public function get(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'tax_zone' => $this->taxZone,
            // Manually create arrays from public properties
            'items' => $this->items->map(fn(CartItemDTO $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'category' => $item->category,
                'metadata' => $item->metadata,
            ])->values()->toArray(), // Add values() to ensure sequential numeric keys
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
            // Add calculated values later if needed (subtotal, total, tax etc.)
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

        event(new CartUpdated($this)); // Pass $this

        return $this;
    }

    public function applyDiscount(string $code): static
    {
        // Assuming DiscountDTO constructor takes the code
        $this->discounts->push(new DiscountDTO($code));
        event(new CartUpdated($this)); // Pass $this

        return $this;
    }

    public function addNote(string $note): static
    {
        $this->notes->push($note);
        event(new CartUpdated($this)); // Pass $this

        return $this;
    }

    public function save(): static
    {
        // No need to check for cart existence
        // Ensure ID is set if not already
        if (! $this->id) {
            $this->id = (string) Str::uuid();
        }

        // Construct data array for repository
        $cartData = [
            'id' => $this->id,
            // Use the mapping logic from get() to ensure correct array structure for DTOs
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
            'vat_exempt' => $this->vatExempt, // Pass vat_exempt status
        ];

        $savedId = $this->repository->save($cartData);
        // Update the instance ID with the ID returned from the save operation
        $this->id = $savedId;

        return $this; // Return $this for fluent interface
    }

    public function find(string $id): static
    {
        // Repository now returns an array or null
        $foundCartData = $this->repository->find($id);

        if (! $foundCartData) {
            // Handle not found case
            $this->create(); // Reset to empty state
            $this->id = $id; // Keep the ID we searched for?
            throw new CartException("Cart with ID {$id} not found.");
            // return $this; // Or return empty state
        }

        // Populate $this properties from the found array
        $this->id = $foundCartData['id'];
        $this->userId = $foundCartData['user_id'] ?? null;
        $this->taxZone = $foundCartData['tax_zone'] ?? null;
        $this->currentShippingMethod = $foundCartData['shipping_method'] ?? null;
        $this->vatExempt = $foundCartData['vat_exempt'] ?? false; // Assuming repository returns this key

        // Convert arrays back to DTO collections (similar to constructor)
        $this->items = collect($foundCartData['items'] ?? [])->map(fn($item) => $item instanceof CartItemDTO ? $item : new CartItemDTO(...$item));
        $this->discounts = collect($foundCartData['discounts'] ?? [])->map(fn($discount) => $discount instanceof DiscountDTO ? $discount : new DiscountDTO(...$discount));
        $this->notes = collect($foundCartData['notes'] ?? []);
        $this->extraCosts = collect($foundCartData['extra_costs'] ?? [])->map(fn($cost) => $cost instanceof ExtraCostDTO ? $cost : new ExtraCostDTO(...$cost));

        // Reset non-persistent state?
        $this->shippingVatRate = null;
        $this->shippingVatIncluded = false;

        return $this; // Return $this for fluent interface
    }

    // Delegate total calculation to the calculator service
    public function total(): float
    {
        return $this->calculator->getTotal($this);
    }

    public function clone(): static
    {
        // Create a new instance state based on the current one, but with a new ID
        // AddItemToCartAction no longer needs Dispatcher
        $clonedCart = new static(
            repository: $this->repository, // Keep the same repository
            calculator: $this->calculator, // Add calculator instance
            addItemAction: $this->addItemAction, // Add action instance (no deps needed)
            id: (string) Str::uuid(), // Generate a new ID for the clone
            userId: $this->userId, // Keep user ID
            taxZone: $this->taxZone,
            items: $this->items->map(fn(CartItemDTO $item) => clone $item)->all(), // Deep clone items
            discounts: $this->discounts->map(fn(DiscountDTO $discount) => clone $discount)->all(), // Deep clone discounts
            notes: $this->notes->all(), // Notes are likely strings, shallow clone ok
            extraCosts: $this->extraCosts->map(fn(ExtraCostDTO $cost) => clone $cost)->all(), // Deep clone extra costs
            shippingMethod: $this->currentShippingMethod,
            vatExempt: $this->vatExempt
            // shippingVatRate and shippingVatIncluded are private, handle if needed
        );

        // How should clone behave? Return new instance or modify current?
        // Let's return a new instance, which is more conventional for clone.
        // Or modify $this? The original code modified $this->cart. Let's modify $this for now to match fluent style.

        $this->id = $clonedCart->id;
        $this->userId = $clonedCart->userId;
        $this->taxZone = $clonedCart->taxZone;
        $this->items = $clonedCart->getItems(); // Use getter to get the collection
        $this->discounts = $clonedCart->getDiscounts();
        $this->notes = $clonedCart->getNotes();
        $this->extraCosts = $clonedCart->getExtraCosts();
        $this->currentShippingMethod = $clonedCart->currentShippingMethod; // Access directly if needed
        $this->vatExempt = $clonedCart->vatExempt;
        $this->shippingVatRate = $clonedCart->shippingVatRate;
        $this->shippingVatIncluded = $clonedCart->shippingVatIncluded;

        // Should clone fire an event? Probably not.

        return $this;
    }

    // Update merge signature to accept SimpleCart
    public function merge(SimpleCart $otherCart): static
    {
        // No need to check for cart existence

        // Merge items - simplistic merge, just add all items from other cart
        // More sophisticated merge might update quantities if item exists
        foreach ($otherCart->getItems() as $item) {
            // Need to ensure $item is CartItemDTO
            if ($item instanceof CartItemDTO) {
                $this->items->push(clone $item); // Clone item before adding
            }
        }

        // Merge discounts - simplistic merge, add all discounts
        foreach ($otherCart->getDiscounts() as $discount) {
            // Need to ensure $discount is DiscountDTO
            if ($discount instanceof DiscountDTO) {
                $this->discounts->push(clone $discount); // Clone discount
            }
        }

        // Merge notes? Extra costs? Shipping? Tax Zone? User ID?
        // For now, only merging items and discounts as per original code.

        event(new CartUpdated($this)); // Pass $this

        return $this;
    }

    // --- Methods to keep in SimpleCart (State modifiers, getters) ---

    public function addExtraCost(ExtraCostDTO $cost): static
    {
        $this->extraCosts->push($cost);
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

    // --- Calculation methods delegated to CartCalculator ---

    public function getSubtotal(): float
    {
        return $this->calculator->getSubtotal($this);
    }

    public function getItemCount(): int
    {
        return $this->calculator->getItemCount($this);
    }

    // getShippingCost is removed as it was duplicate of getShippingAmount
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

    // --- Getters for state needed by calculator/providers ---

    public function getShippingMethod(): ?string // Keep getter
    {
        return $this->currentShippingMethod;
    }

    public function getShippingVatInfo(): array // Keep getter
    {
        return [
            'rate' => $this->shippingVatRate,
            'included' => $this->shippingVatIncluded,
        ];
    }

    public function isVatExempt(): bool // Keep getter
    {
        return $this->vatExempt;
    }

    // --- Basic Data Getters ---

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
