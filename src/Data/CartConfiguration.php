<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Data;

readonly class CartConfiguration
{
    public function __construct(
        public int $ttlDays,
        public ?float $freeShippingThreshold,
        public array $taxSettings,
        public array $discounts,
        public string $defaultTaxZone = 'US',
        public bool $allowDiscountStacking = false,
        public int $maxDiscountCodes = 3,
    ) {}

    public static function fromConfig(array $config): self
    {
        // Check if the shipping config exists and has the threshold key
        $threshold = 100.0; // default
        if (isset($config['shipping']) && array_key_exists('free_shipping_threshold', $config['shipping'])) {
            $threshold = $config['shipping']['free_shipping_threshold'];
        }

        // Convert 0 or null to null to disable free shipping
        if ($threshold === 0 || $threshold === 0.0 || $threshold === null) {
            $threshold = null;
        } else {
            $threshold = (float) $threshold;
        }

        return new self(
            ttlDays: $config['storage']['ttl_days'] ?? 30,
            freeShippingThreshold: $threshold,
            taxSettings: $config['tax']['settings']['zones'] ?? [],
            discounts: $config['discounts']['codes'] ?? [],
            defaultTaxZone: $config['tax']['default_zone'] ?? 'US',
            allowDiscountStacking: $config['discounts']['allow_stacking'] ?? false,
            maxDiscountCodes: $config['discounts']['max_discount_codes'] ?? 3,
        );
    }

    public function getTaxSettings(string $zone): ?array
    {
        return $this->taxSettings[$zone] ?? null;
    }
}
