# A simple cart for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/andreilungeanu/simple-cart/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/andreilungeanu/simple-cart/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andreilungeanu/simple-cart.svg?style=flat-square)](https://packagist.org/packages/andreilungeanu/simple-cart)

A flexible shopping cart implementation for Laravel with support for multiple discounts, tax calculations, and extra costs.

## Features

- Fluent interface for cart operations
- Support for multiple discount types (fixed, percentage)
- VAT rates based on country zones
- Extra costs handling (gift wrapping, handling fees, etc.)
- Cart persistence with automatic calculation storage
- Event-driven architecture
- Comprehensive test coverage

## Installation

```bash
composer require andreilungeanu/simple-cart
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="simple-cart-migrations"
php artisan migrate
```

## Basic Usage

```php
use AndreiLungeanu\SimpleCart\Facades\SimpleCart;
use AndreiLungeanu\SimpleCart\DTOs\CartItemDTO;
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;

// Add items to cart
SimpleCart::create()
    ->addItem(new CartItemDTO(
        id: '1',
        name: 'Product Name',
        price: 99.99,
        quantity: 1
    ))
    ->save();

// Apply discounts
SimpleCart::applyDiscount('SUMMER10');

// Add extra costs
SimpleCart::get()->addExtraCost(new ExtraCostDTO(
    name: 'Gift Wrapping',
    amount: 5.00
));

// Get totals
$total = SimpleCart::total();
```

## Real-World Examples

### B2B vs B2C Handling

```php
class OrderController
{
    public function store(Request $request)
    {
        $cart = new CartDTO(
            taxZone: $request->country_code,
            vatExempt: $this->shouldBeVatExempt($request)
        );

        // B2B: Company with VAT number - no VAT charged
        if ($request->company_vat_number && $this->validateVatNumber($request->company_vat_number)) {
            $cart->setVatExempt(true);
        }

        // Add products with different VAT rates
        $cart->addItem(new CartItemDTO(
            id: '1',
            name: 'Business Software License',
            price: 299.99,
            quantity: 5,
            category: 'digital', // Digital goods might have different VAT
            metadata: [
                'license_type' => 'business',
                'seats' => 5,
            ]
        ));
    }
}
```

### Volume-Based Shipping

```php
class WarehouseShippingProvider implements ShippingRateProvider
{
    public function getRate(CartDTO $cart, string $method): array
    {
        $totalVolume = $this->calculateVolume($cart);
        $totalWeight = $this->calculateWeight($cart);
        
        // Real carrier API call
        $rate = $this->carrierApi->getRates([
            'volume' => $totalVolume,
            'weight' => $totalWeight,
            'destination' => $cart->taxZone,
            'service' => $method,
        ]);

        // Handle included/excluded VAT based on carrier
        $vatIncluded = $this->carrierIncludesVat($rate->carrier);
        
        return [
            'amount' => $rate->amount,
            'vat_rate' => $vatIncluded ? $this->extractVatRate($rate) : null,
            'vat_included' => $vatIncluded,
            'metadata' => [
                'carrier' => $rate->carrier,
                'service' => $rate->service,
                'delivery_estimate' => $rate->deliveryDays,
                'tracking_available' => $rate->hasTracking,
            ]
        ];
    }
}
```

### Special Pricing and Discounts

```php
// Tiered pricing based on quantity
$cart->addItem(new CartItemDTO(
    id: '1',
    name: 'Bulk Product',
    price: $this->getTieredPrice($quantity), // e.g., 10+ units = 10% off
    quantity: $quantity,
    metadata: [
        'original_price' => $originalPrice,
        'tier_discount' => $discountPercent,
    ]
));

// Bundle pricing
$bundleItems = [
    new CartItemDTO(id: '1', name: 'Main Product', price: 100.00, quantity: 1),
    new CartItemDTO(id: '2', name: 'Bundle Item', price: 0.00, quantity: 1, metadata: ['bundle_parent' => '1']),
];

foreach ($bundleItems as $item) {
    $cart->addItem($item);
}

// Seasonal tax adjustments
if ($this->isHolidaySeason()) {
    $cart->addItem(new CartItemDTO(
        id: '1',
        name: 'Holiday Gift Set',
        price: 49.99,
        quantity: 1,
        category: 'gift_sets', // Special holiday VAT rate
    ));
}
```

### Multi-Currency Support

```php
class InternationalCart
{
    public function createCart(string $currency): CartDTO
    {
        $cart = new CartDTO(
            taxZone: $this->getDefaultZoneForCurrency($currency),
            metadata: ['display_currency' => $currency]
        );

        // Add items with currency conversion
        $cart->addItem(new CartItemDTO(
            id: '1',
            name: 'International Product',
            price: $this->convertToLocalPrice(99.99, $currency),
            quantity: 1,
            metadata: [
                'base_price_usd' => 99.99,
                'exchange_rate' => $this->getExchangeRate($currency),
            ]
        ));

        return $cart;
    }
}
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="simple-cart-config"
```

Available configuration options:

```php
return [
    'storage' => [
        'driver' => 'database',
        'ttl' => 30 * 24 * 60 * 60, // 30 days
    ],
    'tax' => [
        'default_zone' => 'US',
        'rates' => [
            'US' => 0.0725,
            'EU' => 0.21,
        ],
    ],
    'shipping' => [
        'provider' => DefaultShippingProvider::class,
        'free_shipping_threshold' => 100.00,
    ],
    // ...more configuration options
];
```


### Shipping Thresholds

```php
'shipping' => [
    'settings' => [
        'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 100.00),
        // ...other settings
    ],
],
```

### Tax Zones and Rates

```php
'tax' => [
    'settings' => [
        'zones' => [
            'RO' => [
                'default_rate' => 0.19,
                'rates_by_category' => [
                    'books' => 0.05,
                    'food' => 0.09,
                ],
            ],
        ],
    ],
],
```

## Shipping & Tax Configuration

### Shipping Configuration

```php
'shipping' => [
    'provider' => DefaultShippingProvider::class,
    'settings' => [
        'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', 100.00),
        'methods' => [
            'standard' => [
                'cost' => env('CART_STANDARD_SHIPPING_COST', 5.99),
                'name' => 'Standard Shipping',
                'vat_included' => false,
                'vat_rate' => null, // Uses cart's VAT rate
            ],
            'express' => [
                'cost' => env('CART_EXPRESS_SHIPPING_COST', 15.99),
                'name' => 'Express Shipping',
                'vat_included' => false,
                'vat_rate' => null,
            ],
        ],
    ],
],
```

### External Shipping Provider Example

```php
class ExternalShippingProvider implements ShippingRateProvider
{
    public function getRate(CartDTO $cart, string $method): array
    {
        // Get rates from external API
        $externalRate = $this->apiClient->getRate([
            'items' => $cart->getItems(),
            'method' => $method,
        ]);

        return [
            'amount' => $externalRate->total,
            'vat_rate' => $externalRate->vatRate ?? null, // Optional specific VAT rate
            'vat_included' => $externalRate->vatIncluded ?? false,
        ];
    }
}
```

### VAT Handling

The cart now supports three main VAT scenarios:

1. Default cart VAT rate:
```php
$shipping = [
    'amount' => 5.99,
    'vat_included' => false,
    'vat_rate' => null // Will use cart's VAT rate
];
```

2. Pre-calculated VAT:
```php
$shipping = [
    'amount' => 15.99,
    'vat_included' => true, // Amount includes VAT
    'vat_rate' => 0.19 // For reporting purposes
];
```

3. Custom VAT rate:
```php
$shipping = [
    'amount' => 10.99,
    'vat_included' => false,
    'vat_rate' => 0.10 // Use specific rate
];
```

### VAT Exemption

```php
// Create VAT exempt cart
$cart = new CartDTO(
    taxZone: 'RO',
    vatExempt: true
);

// VAT exempt calculations
$total = $cart->calculateTotal(); // All amounts exclude VAT
$taxAmount = $cart->getTaxAmount(); // Returns 0.0
```

## Advanced Usage Examples

### Tax Handling

```php
// Basic tax calculation
$cart = new CartDTO(taxZone: 'RO');
$cart->addItem(new CartItemDTO(
    id: '1',
    name: 'Regular Item',
    price: 100.00,
    quantity: 1
)); // Will use default 19% VAT

// Category-specific tax rates
$cart->addItem(new CartItemDTO(
    id: '2',
    name: 'Book',
    price: 50.00,
    quantity: 1,
    category: 'books'
)); // Will use 5% VAT

// VAT Exemption (e.g., B2B customers)
$cart->setVatExempt(true); // All items become VAT exempt

// Mixed shipping & VAT scenarios
$cart = new CartDTO(taxZone: 'RO');
$cart->addItem(/* ...item details... */);

// 1. Standard shipping with cart's VAT
$standardShipping = [
    'amount' => 5.99,
    'vat_included' => false,
    'vat_rate' => null // Uses cart's VAT rate
];

// 2. Express shipping with included VAT
$expressShipping = [
    'amount' => 15.99,
    'vat_included' => true,
    'vat_rate' => 0.19 // For display/reporting
];

// 3. Custom shipping with specific VAT
$customShipping = [
    'amount' => 10.99,
    'vat_included' => false,
    'vat_rate' => 0.10 // Custom rate
];

$cart->setShippingMethod('custom', $customShipping);
```

### Full Cart Example with Multiple Scenarios

```php
// Initialize cart with tax zone
$cart = new CartDTO(taxZone: 'RO');

// Add regular item (19% VAT)
$cart->addItem(new CartItemDTO(
    id: '1',
    name: 'Electronics',
    price: 1000.00,
    quantity: 1
));

// Add reduced VAT item (5%)
$cart->addItem(new CartItemDTO(
    id: '2',
    name: 'Book',
    price: 50.00,
    quantity: 2,
    category: 'books'
));

// Add shipping with custom VAT
$cart->setShippingMethod('express', [
    'amount' => 15.99,
    'vat_included' => true,
    'vat_rate' => 0.19
]);

// Add extra costs
$cart->addExtraCost(new ExtraCostDTO(
    name: 'Gift Wrapping',
    amount: 5.00
));

// Calculate totals
$subtotal = $cart->getSubtotal(); // 1100.00
$tax = $cart->getTaxAmount(); // 195.00 (190 for electronics + 5 for books)
$shipping = $cart->getShippingAmount(); // 15.99
$total = $cart->calculateTotal(); // 1315.99
```

## Shipping Providers

### Default Fixed Rate Shipping

```php
// Basic configuration with fixed rates
'shipping' => [
    'settings' => [
        'free_shipping_threshold' => 100.00,
        'methods' => [
            'standard' => [
                'cost' => 5.99,
                'name' => 'Standard Shipping',
            ],
        ],
    ],
],
```

### External Shipping Provider Example

```php
class ExternalShippingProvider implements ShippingRateProvider
{
    public function getRate(CartDTO $cart, string $method): array
    {
        // Get rates from external API
        $externalRate = $this->apiClient->getRate([
            'items' => $cart->getItems(),
            'method' => $method,
        ]);

        return [
            'amount' => $externalRate->total,
            'vat_rate' => $externalRate->taxRate,
            'vat_included' => $externalRate->taxIncluded,
        ];
    }
}
```

## Tax Handling

The cart handles VAT rates per country:

```php
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;
use AndreiLungeanu\SimpleCart\Services\DefaultTaxProvider;

// Create cart with German VAT (19%)
$cart = new CartDTO(taxZone: 'DE');

// The total will automatically include:
// - Item subtotal
// - Applied VAT rate for the zone
// - Shipping cost (with VAT if applicable)
// - Extra costs (with VAT if applicable)
// - Applied discounts
$total = $cart->calculateTotal();

// You can get individual amounts
$subtotal = $cart->getSubtotal();
$tax = $cart->getTaxAmount();
$shipping = $cart->getShippingAmount();
$discounts = $cart->getDiscountAmount();
```

## Events

The package fires the following events:

- CartCreated
- CartUpdated
- CartCleared

## Advanced Usage

### Custom Discount Rules

```php
use AndreiLungeanu\SimpleCart\DTOs\DiscountDTO;

// Create a discount with minimum amount
$discount = new DiscountDTO(
    code: 'SAVE20',
    type: 'percentage',
    value: 20,
    minimumAmount: 100.00
);

// Apply category-specific discount
$discount = new DiscountDTO(
    code: 'TECH15',
    type: 'percentage',
    value: 15,
    appliesTo: 'electronics'
);
```

### Working with Extra Costs

```php
use AndreiLungeanu\SimpleCart\DTOs\ExtraCostDTO;

// Add percentage-based handling fee
SimpleCart::get()->addExtraCost(new ExtraCostDTO(
    name: 'Handling',
    amount: 5,
    type: 'percentage'
));

// Add insurance based on cart value
if (SimpleCart::total() > 1000) {
    SimpleCart::get()->addExtraCost(new ExtraCostDTO(
        name: 'Insurance',
        amount: 25,
        type: 'fixed'
    ));
}
```

### Custom Shipping Calculations

```php
use AndreiLungeanu\SimpleCart\Contracts\ShippingRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

class InternationalShippingProvider implements ShippingRateProvider
{
    public function getRate(CartDTO $cart, string $method): array
    {
        $weight = $this->calculateTotalWeight($cart);
        $destination = $cart->taxZone;

        return match($method) {
            'standard' => [
                'amount' => $this->calculateInternationalRate($weight, $destination),
                'vat_rate' => 0.19, // Example VAT rate
                'vat_included' => false,
            ],
            'express' => [
                'amount' => $this->calculateExpressInternationalRate($weight, $destination),
                'vat_rate' => 0.19, // Example VAT rate
                'vat_included' => false,
            ],
            default => [
                'amount' => 0.0,
                'vat_rate' => 0.0,
                'vat_included' => false,
            ],
        };
    }

    public function getAvailableMethods(CartDTO $cart): array
    {
        return [
            'standard' => [
                'name' => 'International Standard',
                'estimate_days' => '7-14',
                'tracking' => true,
            ],
            'express' => [
                'name' => 'International Express',
                'estimate_days' => '2-3',
                'tracking' => true,
            ],
        ];
    }
}

// Register in your ServiceProvider
$this->app->bind(ShippingRateProvider::class, InternationalShippingProvider::class);
```

You can switch providers based on conditions:

```php
class ShippingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ShippingRateProvider::class, function ($app) {
            return match(config('app.environment')) {
                'local' => new TestShippingProvider(),
                'production' => new RealTimeShippingProvider(),
                default => new DefaultShippingProvider(),
            };
        });
    }
}
```

### Cart Management

```php
// Save cart for later
$cartId = SimpleCart::save()->get()->id;

// Retrieve saved cart
SimpleCart::find($cartId);

// Clone cart
$newCart = SimpleCart::clone();

// Merge carts (e.g., when user logs in)
$guestCart = SimpleCart::find($guestCartId);
$userCart = SimpleCart::find($userCartId);
$userCart->merge($guestCart);
```

## Advanced Configuration Examples

### Custom Tax Rules

```php
// Custom tax configuration
'tax' => [
    'settings' => [
        'zones' => [
            'EU' => [
                'name' => 'European Union',
                'default_rate' => 0.21,
                'apply_to_shipping' => true,
                'rates_by_category' => [
                    'books' => 0.06,      // Reduced rate for books
                    'medical' => 0.0,      // Tax exempt medical supplies
                    'children' => 0.06,    // Reduced rate for children's items
                ],
                'thresholds' => [
                    'registration' => 10000, // VAT registration threshold
                    'distance' => 150000,    // Distance selling threshold
                ],
            ],
        ],
    ],
],

// Usage with categories
$cart->addItem(new CartItemDTO(
    id: '1',
    name: 'Medical Supply',
    price: 100.00,
    category: 'medical',
    quantity: 1
));

// Multiple shipping options with VAT
'shipping' => [
    'settings' => [
        'zones' => [
            'EU' => [
                'methods' => [
                    'local' => [
                        'base_cost' => 4.99,
                        'item_cost' => 1.00,
                        'vat_rate' => 0.21,
                        'restrictions' => [
                            'max_weight' => 20,
                            'max_items' => 10,
                        ],
                    ],
                ],
            ],
        ],
    ],
],
```

## Tax & Shipping Providers

### Custom Tax Provider

```php
use AndreiLungeanu\SimpleCart\Contracts\TaxRateProvider;
use AndreiLungeanu\SimpleCart\DTOs\CartDTO;

class CustomTaxProvider implements TaxRateProvider
{
    public function getRate(CartDTO $cart): float
    {
        // Example: Different rates based on total amount
        $subtotal = $cart->getSubtotal();
        
        return match(true) {
            $subtotal >= 1000 => 0.05, // 5% for large purchases
            $subtotal >= 500 => 0.07,  // 7% for medium purchases
            default => 0.10,           // 10% for small purchases
        };
    }

    public function getAvailableZones(): array
    {
        return [
            'ZONE1' => ['rate' => 0.05, 'name' => 'Premium Zone'],
            'ZONE2' => ['rate' => 0.07, 'name' => 'Standard Zone'],
            'ZONE3' => ['rate' => 0.10, 'name' => 'Basic Zone'],
        ];
    }
}
```

Register your custom providers in a service provider:

```php
use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxRateProvider::class, function ($app) {
            return match(config('app.environment')) {
                'production' => new LiveTaxProvider(),
                'testing' => new TestTaxProvider(),
                default => new DefaultTaxProvider(),
            };
        });
    }
}
```

## Cookbook

### Implementing Cart Recovery

```php
// In your scheduler:
use AndreiLungeanu\SimpleCart\Models\Cart;

Cart::where('updated_at', '<', now()->subDays(7))
    ->where('user_id', '!=', null)
    ->each(function ($cart) {
        // Send recovery email
        Mail::to($cart->user->email)->send(new CartRecoveryMail($cart));
    });
```

### Custom Tax Calculation

```php
class CustomTaxCalculator implements Calculator
{
    public function calculate(CartDTO $cart): float
    {
        return match($cart->taxZone) {
            'US-CA' => $cart->getSubtotal() * 0.0725,
            'US-NY' => $cart->getSubtotal() * 0.08875,
            default => 0
        };
    }
}

// Register in your ServiceProvider
$this->app->bind(Calculator::class, CustomTaxCalculator::class);
```

## API Reference

### SimpleCart Methods

| Method | Description |
|--------|-------------|
| `create()` | Creates a new cart instance |
| `addItem(CartItemDTO $item)` | Adds item to cart |
| `updateQuantity(string $id, int $quantity)` | Updates item quantity |
| `removeItem(string $id)` | Removes item from cart |
| `clear()` | Empties the cart |
| `save()` | Persists cart to storage |
| `find(string $id)` | Retrieves cart by ID |

### Events

| Event | When It's Fired |
|-------|----------------|
| `CartCreated` | When a new cart is created |
| `CartUpdated` | When cart content changes |
| `CartCleared` | When cart is emptied |

## Credits

- [Andrei Lungeanu](https://github.com/andreilungeanu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
