<?php

namespace AndreiLungeanu\SimpleCart\DTOs;

use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\Services\DiscountCalculator;
use AndreiLungeanu\SimpleCart\Services\ShippingCalculator;
use AndreiLungeanu\SimpleCart\Services\TaxCalculator;
use Illuminate\Support\Collection;

class CartDTO
{
    protected Collection $items;

    protected Collection $discounts;

    protected Collection $notes;

    protected Collection $extraCosts;

    private ?float $shippingVatRate = null;

    private bool $shippingVatIncluded = false;

    private bool $vatExempt = false;

    protected ?string $currentShippingMethod = null;

    public function __construct(
        public readonly string $id = '',
        array $items = [],
        public readonly ?string $userId = null,
        array $discounts = [],
        array $notes = [],
        array $extraCosts = [],
        ?string $shippingMethod = null,
        public readonly ?string $taxZone = null,
        bool $vatExempt = false,
    ) {
        $this->items = collect($items)->map(function ($item) {
            return $item instanceof CartItemDTO ? $item : new CartItemDTO(...$item);
        });
        $this->discounts = collect($discounts);
        $this->notes = collect($notes);
        $this->extraCosts = collect($extraCosts);
        $this->vatExempt = $vatExempt;
        $this->currentShippingMethod = $shippingMethod;
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

    public function updateItemQuantity(string $itemId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $this->items->transform(function ($item) use ($itemId, $quantity) {
            if ($item->id === $itemId) {
                return $item->withQuantity($quantity);
            }

            return $item;
        });
    }

    public function applyDiscount(string $code): void
    {
        $this->discounts->push(new DiscountDTO($code));
    }

    public function addNote(string $note): void
    {
        $this->notes->push($note);
    }

    public function addExtraCost(ExtraCostDTO $cost): void
    {
        $this->extraCosts->push($cost);
    }

    public function addItem(CartItemDTO $item): void
    {
        $this->items->push($item);
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    private function round(float $amount): float
    {
        return round($amount, 2);
    }

    public function getSubtotal(): float
    {
        return $this->round(
            $this->items->sum(
                fn($item) => $item->price * $item->quantity
            )
        );
    }

    public function getItemCount(): int
    {
        return $this->items->sum(fn($item) => $item->quantity);
    }

    public function getShippingCost(): float
    {
        if (! $this->currentShippingMethod) {
            return 0.0;
        }

        return app(ShippingCalculator::class)->calculate($this);
    }

    public function getTaxAmount(): float
    {
        if ($this->isVatExempt()) {
            return 0.0;
        }

        $itemsTax = app(TaxCalculator::class)->calculate($this);
        $shippingTax = $this->currentShippingMethod && !$this->shippingVatIncluded
            ? $this->calculateShippingVat()
            : 0.0;
        $extraCostsTax = $this->getExtraCostsTax();

        return $this->round($itemsTax + $shippingTax + $extraCostsTax);
    }

    public function getShippingAmount(): float
    {
        return app(ShippingCalculator::class)->calculate($this);
    }

    public function getDiscountAmount(): float
    {
        return app(DiscountCalculator::class)->calculate($this);
    }

    public function calculateTotal(): float
    {
        return $this->getSubtotal() +
            $this->getShippingAmount() +
            $this->getTaxAmount() +
            $this->getExtraCostsTotal() -
            $this->getDiscountAmount();
    }

    private function calculateExtraCosts(): float
    {
        return $this->extraCosts->sum(function (ExtraCostDTO $cost) {
            if ($cost->type === 'percentage') {
                return ($this->getSubtotal() * $cost->amount) / 100;
            }

            return $cost->amount;
        });
    }

    private function getExtraCostsTax(): float
    {
        if ($this->isVatExempt()) {
            return 0.0;
        }

        $rate = $this->defaultVatRate();
        return $this->round($this->getExtraCostsTotal() * $rate);
    }

    public function getExtraCostsTotal(): float
    {
        return round($this->calculateExtraCosts(), 2);
    }

    public function setShippingMethod(string $method, array $shippingInfo): void
    {
        if (array_key_exists('vat_rate', $shippingInfo) && is_numeric($shippingInfo['vat_rate'])) {
            if ($shippingInfo['vat_rate'] < 0 || $shippingInfo['vat_rate'] > 1) {
                throw new \InvalidArgumentException('VAT rate must be between 0 and 1');
            }
        }

        $this->currentShippingMethod = $method;
        $this->shippingVatRate = $shippingInfo['vat_rate'] ?? null;
        $this->shippingVatIncluded = $shippingInfo['vat_included'] ?? false;
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

    public function calculateShippingVat(): float
    {
        if ($this->isVatExempt() || !$this->currentShippingMethod) {
            return 0.0;
        }

        $rate = $this->shippingVatRate ?? $this->defaultVatRate();
        return $this->round($this->getShippingAmount() * $rate);
    }

    protected function defaultVatRate(): float
    {
        return app(TaxRateProvider::class)->getRate($this);
    }

    public function setVatExempt(bool $exempt = true): void
    {
        $this->vatExempt = $exempt;
    }

    public function isVatExempt(): bool
    {
        return $this->vatExempt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'items' => $this->getItems()->toArray(),
            'user_id' => $this->userId,
            'discounts' => $this->getDiscounts()->toArray(),
            'notes' => $this->getNotes()->toArray(),
            'extra_costs' => $this->getExtraCosts()->toArray(),
            'shipping_method' => $this->currentShippingMethod,
            'tax_zone' => $this->taxZone,
        ];
    }
}
