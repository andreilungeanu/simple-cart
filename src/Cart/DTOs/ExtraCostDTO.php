<?php

namespace AndreiLungeanu\SimpleCart\Cart\DTOs;

readonly class ExtraCostDTO
{
    public function __construct(
        public string $name,
        public float $amount,
        public string $type = 'fixed',
        public ?string $description = null,
        public ?float $vatRate = null,
        public bool $vatIncluded = false,
    ) {}

    public function shouldCalculateVat(): bool
    {
        return ! $this->vatIncluded && $this->vatRate !== null;
    }

    /**
     * Create a new DTO instance from an array.
     *
     * @param  array  $data  Associative array with extra cost data.
     *
     * @throws \InvalidArgumentException If required keys are missing or data is invalid.
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name'], $data['amount'], $data['type'])) {
            throw new \InvalidArgumentException('Extra cost array must contain name, amount, and type.');
        }

        if (! in_array($data['type'], ['fixed', 'percentage'])) {
            throw new \InvalidArgumentException('Invalid extra cost type.');
        }
        if ($data['amount'] < 0 && $data['type'] === 'fixed') {
            throw new \InvalidArgumentException('Fixed extra cost amount cannot be negative.');
        }

        return new self(
            name: $data['name'],
            amount: (float) $data['amount'],
            type: $data['type'],
            description: $data['description'] ?? null,
            vatRate: isset($data['vatRate']) ? (float) $data['vatRate'] : null,
            vatIncluded: $data['vatIncluded'] ?? false,
        );
    }

    /**
     * Convert the DTO instance to an array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'amount' => $this->amount,
            'type' => $this->type,
            'description' => $this->description,
            'vatRate' => $this->vatRate,
            'vatIncluded' => $this->vatIncluded,
        ];
    }
}
