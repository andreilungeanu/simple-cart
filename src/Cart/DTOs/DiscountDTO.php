<?php

namespace AndreiLungeanu\SimpleCart\Cart\DTOs;

readonly class DiscountDTO
{
    public function __construct(
        public string $code,
        public string $type = 'fixed',
        public float $value = 0,
        public ?string $appliesTo = null,
        public ?string $minimumAmount = null,
        public ?string $expiresAt = null,
    ) {
        if (!in_array($this->type, ['fixed', 'percentage', 'shipping'])) {
            throw new \InvalidArgumentException('Invalid discount type.');
        }
        if ($this->value < 0) {
            throw new \InvalidArgumentException('Discount value cannot be negative.');
        }
    }

    /**
     * Create a new DTO instance from an array.
     *
     * @param array $data Associative array with discount data.
     * @return self
     * @throws \InvalidArgumentException If required keys are missing or data is invalid.
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['code'], $data['type'], $data['value'])) {
            throw new \InvalidArgumentException('Discount array must contain code, type, and value.');
        }

        return new self(
            code: $data['code'],
            type: $data['type'],
            value: (float) $data['value'],
            appliesTo: $data['appliesTo'] ?? null,
            minimumAmount: $data['minimumAmount'] ?? null,
            expiresAt: $data['expiresAt'] ?? null,
        );
    }

    /**
     * Convert the DTO instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'appliesTo' => $this->appliesTo,
            'minimumAmount' => $this->minimumAmount,
            'expiresAt' => $this->expiresAt,
        ];
    }
}
