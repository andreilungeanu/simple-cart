<?php

namespace AndreiLungeanu\SimpleCart;

use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO; // Re-add CartDTO import
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;
use AndreiLungeanu\SimpleCart\Events\CartCleared;
use AndreiLungeanu\SimpleCart\Events\CartCreated;
use AndreiLungeanu\SimpleCart\Events\CartUpdated;
use AndreiLungeanu\SimpleCart\Exceptions\CartException;
use AndreiLungeanu\SimpleCart\Repositories\CartRepository;
use AndreiLungeanu\SimpleCart\Services\DiscountCalculator;
use AndreiLungeanu\SimpleCart\Services\ShippingCalculator;
use AndreiLungeanu\SimpleCart\Services\TaxCalculator;
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

    public function __construct(
        protected readonly CartRepository $repository,
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

    public function addItem(CartItemDTO $item): static
    {
        // No need to check for cart existence, state is inherent
        $this->items->push($item);
        event(new CartUpdated($this)); // Pass $this

        return $this;
    }

    public function removeItem(string $itemId): static
    {
        // Implementation
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

        // Construct a DTO for repository interaction (temporary)
        // Need to import CartDTO again for this
        $cartDTO = new \AndreiLungeanu\SimpleCart\DTOs\CartDTO(
            id: $this->id,
            items: $this->items->toArray(), // Pass raw array if DTO constructor expects it
            userId: $this->userId,
            discounts: $this->discounts->toArray(),
            notes: $this->notes->toArray(),
            extraCosts: $this->extraCosts->toArray(),
            shippingMethod: $this->currentShippingMethod,
            taxZone: $this->taxZone,
            vatExempt: $this->vatExempt,
        );

        $savedId = $this->repository->save($cartDTO);
        // Optionally update $this->id if repository returns a potentially different ID
        // $this->id = $savedId;

        return $this;
    }

    public function find(string $id): static
    {
        // Need CartDTO import here too
        $foundCartDTO = $this->repository->find($id);

        if (! $foundCartDTO) {
            // Handle not found case - maybe throw exception or return $this in empty state?
            // Let's reset to empty state for now
            $this->create(); // Reset to empty state
            $this->id = $id; // Keep the ID we searched for? Or clear it?
            throw new CartException("Cart with ID {$id} not found."); // Or throw
            // return $this;
        }

        // Populate $this properties from the found DTO
        $this->id = $foundCartDTO->id;
        $this->userId = $foundCartDTO->userId;
        $this->taxZone = $foundCartDTO->taxZone;
        $this->items = $foundCartDTO->getItems(); // Assuming getItems returns Collection<CartItemDTO>
        $this->discounts = $foundCartDTO->getDiscounts(); // Assuming getDiscounts returns Collection<DiscountDTO>
        $this->notes = $foundCartDTO->getNotes();
        $this->extraCosts = $foundCartDTO->getExtraCosts(); // Assuming getExtraCosts returns Collection<ExtraCostDTO>
        $this->currentShippingMethod = $foundCartDTO->getShippingMethod();
        $this->vatExempt = $foundCartDTO->isVatExempt();
        // Note: shippingVatRate and shippingVatIncluded are not in CartDTO constructor/toArray, need to handle if needed

        return $this;
    }

    public function total(): float
    {
        // Calculation logic will be moved here
        // For now, placeholder call
        return $this->calculateTotal();
    }

    public function clone(): static
    {
        // Create a new instance state based on the current one, but with a new ID
        $clonedCart = new static(
            repository: $this->repository, // Keep the same repository
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

    // Keep CartDTO for signature for now, but logic uses $this
    public function merge(CartDTO $otherCart): static
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

    // --- Methods moved from CartDTO ---

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

    // --- Calculation methods moved from CartDTO ---

    private function round(float $amount): float
    {
        return round($amount, 2);
    }

    public function getSubtotal(): float
    {
        return $this->round(
            $this->items->sum(
                fn(CartItemDTO $item) => $item->price * $item->quantity // Added type hint
            )
        );
    }

    public function getItemCount(): int
    {
        return $this->items->sum(fn(CartItemDTO $item) => $item->quantity); // Added type hint
    }

    public function getShippingCost(): float
    {
        if (! $this->currentShippingMethod) {
            return 0.0;
        }
        // Pass $this instead of CartDTO instance
        return app(ShippingCalculator::class)->calculate($this);
    }

    public function getShippingAmount(): float // Renamed from getShippingCost in DTO? Let's keep getShippingAmount
    {
        if (! $this->currentShippingMethod) {
            return 0.0;
        }
        // Pass $this instead of CartDTO instance
        return app(ShippingCalculator::class)->calculate($this);
    }

    public function getTaxAmount(): float
    {
        if ($this->isVatExempt()) {
            return 0.0;
        }

        // Pass $this instead of CartDTO instance
        $itemsTax = app(TaxCalculator::class)->calculate($this);
        $shippingTax = $this->currentShippingMethod && ! $this->shippingVatIncluded
            ? $this->calculateShippingVat()
            : 0.0;
        $extraCostsTax = $this->getExtraCostsTax();

        return $this->round($itemsTax + $shippingTax + $extraCostsTax);
    }

    public function getDiscountAmount(): float
    {
        // Pass $this instead of CartDTO instance
        return app(DiscountCalculator::class)->calculate($this);
    }

    public function calculateTotal(): float // This now exists
    {
        // Ensure all calculation methods use $this properties
        return $this->round( // Added rounding to final total
            $this->getSubtotal() +
                $this->getShippingAmount() + // Use getShippingAmount
                $this->getTaxAmount() +
                $this->getExtraCostsTotal() -
                $this->getDiscountAmount()
        );
    }

    private function calculateExtraCosts(): float
    {
        return $this->extraCosts->sum(function (ExtraCostDTO $cost) {
            if ($cost->type === 'percentage') {
                // Use $this->getSubtotal()
                return ($this->getSubtotal() * $cost->amount) / 100;
            }

            return $cost->amount;
        });
    }

    public function getExtraCostsTotal(): float // Made public as it's used in calculateTotal
    {
        return round($this->calculateExtraCosts(), 2);
    }

    private function getExtraCostsTax(): float
    {
        if ($this->isVatExempt()) {
            return 0.0;
        }

        $rate = $this->defaultVatRate();
        // Use getExtraCostsTotal()
        return $this->round($this->getExtraCostsTotal() * $rate);
    }

    public function getShippingMethod(): ?string // Added getter
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

    public function calculateShippingVat(): float
    {
        if ($this->isVatExempt() || ! $this->currentShippingMethod) {
            return 0.0;
        }

        $rate = $this->shippingVatRate ?? $this->defaultVatRate();
        // Use getShippingAmount()
        return $this->round($this->getShippingAmount() * $rate);
    }

    protected function defaultVatRate(): float
    {
        // Pass $this instead of CartDTO instance
        return app(TaxRateProvider::class)->getRate($this);
    }

    public function isVatExempt(): bool
    {
        return $this->vatExempt;
    }

    // --- End moved methods ---


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
