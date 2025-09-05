<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Data;

readonly class CartConfiguration
{
    public function __construct(
        public int $ttlDays,
        public float $freeShippingThreshold,
        public array $taxSettings,
        public array $shippingMethods,
        public string $defaultTaxZone = 'US',
        public bool $allowDiscountStacking = false,
        public int $maxDiscountCodes = 3,
    ) {}

    public static function fromConfig(array $config): self
    {
        return new self(
            ttlDays: $config['storage']['ttl_days'] ?? 30,
            freeShippingThreshold: $config['shipping']['settings']['free_shipping_threshold'] ?? 100.0,
            taxSettings: $config['tax']['settings']['zones'] ?? [],
            shippingMethods: $config['shipping']['settings']['methods'] ?? [],
            defaultTaxZone: $config['tax']['default_zone'] ?? 'US',
            allowDiscountStacking: $config['discounts']['allow_stacking'] ?? false,
            maxDiscountCodes: $config['discounts']['max_discount_codes'] ?? 3,
        );
    }

    public function getTaxSettings(string $zone): ?array
    {
        return $this->taxSettings[$zone] ?? null;
    }

    public function getShippingMethod(string $method): ?array
    {
        return $this->shippingMethods[$method] ?? null;
    }

    public function getShippingMethods(): array
    {
        return $this->shippingMethods;
    }
}
