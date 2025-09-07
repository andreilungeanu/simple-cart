<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Data;

readonly class CartConfiguration
{
    public function __construct(
        public int $ttlDays,
        public ?float $freeShippingThreshold,
        public array $discounts,
        public bool $allowDiscountStacking = false,
        public int $maxDiscountCodes = 3,
    ) {}

    public static function fromConfig(array $config): self
    {
        $threshold = 100.0; // default
        if (isset($config['shipping']) && array_key_exists('free_shipping_threshold', $config['shipping'])) {
            $threshold = $config['shipping']['free_shipping_threshold'];
        }

        if ($threshold === 0 || $threshold === 0.0 || $threshold === null) {
            $threshold = null;
        } else {
            $threshold = (float) $threshold;
        }

        return new self(
            ttlDays: $config['storage']['ttl_days'] ?? 30,
            freeShippingThreshold: $threshold,
            discounts: $config['discounts']['codes'] ?? [],
            allowDiscountStacking: $config['discounts']['allow_stacking'] ?? false,
            maxDiscountCodes: $config['discounts']['max_discount_codes'] ?? 3,
        );
    }
}
