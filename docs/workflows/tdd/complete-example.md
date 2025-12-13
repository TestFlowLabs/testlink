# Complete TDD Example

This tutorial walks through building a complete `PriceCalculator` class using TDD with TestLink. You'll see the full workflow from start to finish.

## What We're Building

A `PriceCalculator` that:
- Calculates base price from quantity and unit price
- Applies discounts based on user tier
- Adds tax based on region
- Returns a final total

## Project Setup

Ensure you have TestLink installed:

```bash
composer require testflowlabs/test-attributes
composer require --dev testflowlabs/testlink
```

## Part 1: Base Price Calculation

### Red Phase

Create the test file:

:::tabs key:stack
== Pest

```php
<?php
// tests/PriceCalculatorTest.php

use App\Pricing\PriceCalculator;

describe('PriceCalculator', function () {
    describe('calculateBasePrice', function () {
        test('multiplies quantity by unit price', function () {
            $calculator = new PriceCalculator();

            $result = $calculator->calculateBasePrice(
                quantity: 5,
                unitPrice: 100
            );

            expect($result)->toBe(500);
        })->linksAndCovers(PriceCalculator::class.'::calculateBasePrice');

        test('returns zero for zero quantity', function () {
            $calculator = new PriceCalculator();

            $result = $calculator->calculateBasePrice(
                quantity: 0,
                unitPrice: 100
            );

            expect($result)->toBe(0);
        })->linksAndCovers(PriceCalculator::class.'::calculateBasePrice');
    });
});
```

== PHPUnit + Attributes

```php
<?php
// tests/PriceCalculatorTest.php

namespace Tests;

use App\Pricing\PriceCalculator;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class PriceCalculatorTest extends TestCase
{
    #[LinksAndCovers(PriceCalculator::class, 'calculateBasePrice')]
    public function test_multiplies_quantity_by_unit_price(): void
    {
        $calculator = new PriceCalculator();

        $result = $calculator->calculateBasePrice(
            quantity: 5,
            unitPrice: 100
        );

        $this->assertSame(500, $result);
    }

    #[LinksAndCovers(PriceCalculator::class, 'calculateBasePrice')]
    public function test_returns_zero_for_zero_quantity(): void
    {
        $calculator = new PriceCalculator();

        $result = $calculator->calculateBasePrice(
            quantity: 0,
            unitPrice: 100
        );

        $this->assertSame(0, $result);
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/PriceCalculatorTest.php

namespace Tests;

use App\Pricing\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    /**
     * @see \App\Pricing\PriceCalculator::calculateBasePrice
     */
    public function test_multiplies_quantity_by_unit_price(): void
    {
        $calculator = new PriceCalculator();

        $result = $calculator->calculateBasePrice(
            quantity: 5,
            unitPrice: 100
        );

        $this->assertSame(500, $result);
    }

    /**
     * @see \App\Pricing\PriceCalculator::calculateBasePrice
     */
    public function test_returns_zero_for_zero_quantity(): void
    {
        $calculator = new PriceCalculator();

        $result = $calculator->calculateBasePrice(
            quantity: 0,
            unitPrice: 100
        );

        $this->assertSame(0, $result);
    }
}
```

:::

### Green Phase

```php
<?php
// src/Pricing/PriceCalculator.php

namespace App\Pricing;

use TestFlowLabs\TestingAttributes\TestedBy;

class PriceCalculator
{
    #[TestedBy('Tests\PriceCalculatorTest', 'multiplies quantity by unit price')]
    #[TestedBy('Tests\PriceCalculatorTest', 'returns zero for zero quantity')]
    public function calculateBasePrice(int $quantity, int $unitPrice): int
    {
        return $quantity * $unitPrice;
    }
}
```

### Validate

```bash
./vendor/bin/testlink validate
# ✓ All links are valid!
```

## Part 2: Discount Calculation

### Red Phase

Add tests for discount calculation:

:::tabs key:stack
== Pest

```php
describe('applyDiscount', function () {
    test('applies 20% discount for premium tier', function () {
        $calculator = new PriceCalculator();

        $result = $calculator->applyDiscount(
            basePrice: 1000,
            tier: 'premium'
        );

        expect($result)->toBe(800);
    })->linksAndCovers(PriceCalculator::class.'::applyDiscount');

    test('applies 10% discount for gold tier', function () {
        $calculator = new PriceCalculator();

        $result = $calculator->applyDiscount(
            basePrice: 1000,
            tier: 'gold'
        );

        expect($result)->toBe(900);
    })->linksAndCovers(PriceCalculator::class.'::applyDiscount');

    test('applies no discount for standard tier', function () {
        $calculator = new PriceCalculator();

        $result = $calculator->applyDiscount(
            basePrice: 1000,
            tier: 'standard'
        );

        expect($result)->toBe(1000);
    })->linksAndCovers(PriceCalculator::class.'::applyDiscount');
});
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(PriceCalculator::class, 'applyDiscount')]
public function test_applies_20_percent_discount_for_premium_tier(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->applyDiscount(
        basePrice: 1000,
        tier: 'premium'
    );

    $this->assertSame(800, $result);
}

#[LinksAndCovers(PriceCalculator::class, 'applyDiscount')]
public function test_applies_10_percent_discount_for_gold_tier(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->applyDiscount(
        basePrice: 1000,
        tier: 'gold'
    );

    $this->assertSame(900, $result);
}

#[LinksAndCovers(PriceCalculator::class, 'applyDiscount')]
public function test_applies_no_discount_for_standard_tier(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->applyDiscount(
        basePrice: 1000,
        tier: 'standard'
    );

    $this->assertSame(1000, $result);
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Pricing\PriceCalculator::applyDiscount
 */
public function test_applies_20_percent_discount_for_premium_tier(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->applyDiscount(
        basePrice: 1000,
        tier: 'premium'
    );

    $this->assertSame(800, $result);
}

/**
 * @see \App\Pricing\PriceCalculator::applyDiscount
 */
public function test_applies_10_percent_discount_for_gold_tier(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->applyDiscount(
        basePrice: 1000,
        tier: 'gold'
    );

    $this->assertSame(900, $result);
}

/**
 * @see \App\Pricing\PriceCalculator::applyDiscount
 */
public function test_applies_no_discount_for_standard_tier(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->applyDiscount(
        basePrice: 1000,
        tier: 'standard'
    );

    $this->assertSame(1000, $result);
}
```

:::

### Green Phase

```php
#[TestedBy('Tests\PriceCalculatorTest', 'applies 20% discount for premium tier')]
#[TestedBy('Tests\PriceCalculatorTest', 'applies 10% discount for gold tier')]
#[TestedBy('Tests\PriceCalculatorTest', 'applies no discount for standard tier')]
public function applyDiscount(int $basePrice, string $tier): int
{
    $discountRate = match ($tier) {
        'premium' => 0.20,
        'gold' => 0.10,
        default => 0.0,
    };

    return (int) ($basePrice * (1 - $discountRate));
}
```

## Part 3: Tax Calculation

### Red Phase

:::tabs key:stack
== Pest

```php
describe('addTax', function () {
    test('adds 8% tax for US region', function () {
        $calculator = new PriceCalculator();

        $result = $calculator->addTax(
            price: 1000,
            region: 'US'
        );

        expect($result)->toBe(1080);
    })->linksAndCovers(PriceCalculator::class.'::addTax');

    test('adds 20% tax for EU region', function () {
        $calculator = new PriceCalculator();

        $result = $calculator->addTax(
            price: 1000,
            region: 'EU'
        );

        expect($result)->toBe(1200);
    })->linksAndCovers(PriceCalculator::class.'::addTax');
});
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(PriceCalculator::class, 'addTax')]
public function test_adds_8_percent_tax_for_us_region(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->addTax(
        price: 1000,
        region: 'US'
    );

    $this->assertSame(1080, $result);
}

#[LinksAndCovers(PriceCalculator::class, 'addTax')]
public function test_adds_20_percent_tax_for_eu_region(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->addTax(
        price: 1000,
        region: 'EU'
    );

    $this->assertSame(1200, $result);
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Pricing\PriceCalculator::addTax
 */
public function test_adds_8_percent_tax_for_us_region(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->addTax(
        price: 1000,
        region: 'US'
    );

    $this->assertSame(1080, $result);
}

/**
 * @see \App\Pricing\PriceCalculator::addTax
 */
public function test_adds_20_percent_tax_for_eu_region(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->addTax(
        price: 1000,
        region: 'EU'
    );

    $this->assertSame(1200, $result);
}
```

:::

### Green Phase

```php
#[TestedBy('Tests\PriceCalculatorTest', 'adds 8% tax for US region')]
#[TestedBy('Tests\PriceCalculatorTest', 'adds 20% tax for EU region')]
public function addTax(int $price, string $region): int
{
    $taxRate = match ($region) {
        'US' => 0.08,
        'EU' => 0.20,
        default => 0.0,
    };

    return (int) ($price * (1 + $taxRate));
}
```

## Part 4: Calculate Total

### Red Phase

:::tabs key:stack
== Pest

```php
describe('calculateTotal', function () {
    test('combines base price, discount, and tax', function () {
        $calculator = new PriceCalculator();

        // 5 items × $100 = $500 base
        // Premium discount: $500 × 0.80 = $400
        // US tax: $400 × 1.08 = $432
        $result = $calculator->calculateTotal(
            quantity: 5,
            unitPrice: 100,
            tier: 'premium',
            region: 'US'
        );

        expect($result)->toBe(432);
    })->linksAndCovers(PriceCalculator::class.'::calculateTotal');

    test('handles standard tier with EU tax', function () {
        $calculator = new PriceCalculator();

        // 2 items × $50 = $100 base
        // Standard: no discount = $100
        // EU tax: $100 × 1.20 = $120
        $result = $calculator->calculateTotal(
            quantity: 2,
            unitPrice: 50,
            tier: 'standard',
            region: 'EU'
        );

        expect($result)->toBe(120);
    })->linksAndCovers(PriceCalculator::class.'::calculateTotal');
});
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(PriceCalculator::class, 'calculateTotal')]
public function test_combines_base_price_discount_and_tax(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->calculateTotal(
        quantity: 5,
        unitPrice: 100,
        tier: 'premium',
        region: 'US'
    );

    $this->assertSame(432, $result);
}

#[LinksAndCovers(PriceCalculator::class, 'calculateTotal')]
public function test_handles_standard_tier_with_eu_tax(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->calculateTotal(
        quantity: 2,
        unitPrice: 50,
        tier: 'standard',
        region: 'EU'
    );

    $this->assertSame(120, $result);
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Pricing\PriceCalculator::calculateTotal
 */
public function test_combines_base_price_discount_and_tax(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->calculateTotal(
        quantity: 5,
        unitPrice: 100,
        tier: 'premium',
        region: 'US'
    );

    $this->assertSame(432, $result);
}

/**
 * @see \App\Pricing\PriceCalculator::calculateTotal
 */
public function test_handles_standard_tier_with_eu_tax(): void
{
    $calculator = new PriceCalculator();

    $result = $calculator->calculateTotal(
        quantity: 2,
        unitPrice: 50,
        tier: 'standard',
        region: 'EU'
    );

    $this->assertSame(120, $result);
}
```

:::

### Green Phase

```php
#[TestedBy('Tests\PriceCalculatorTest', 'combines base price, discount, and tax')]
#[TestedBy('Tests\PriceCalculatorTest', 'handles standard tier with EU tax')]
public function calculateTotal(
    int $quantity,
    int $unitPrice,
    string $tier,
    string $region
): int {
    $basePrice = $this->calculateBasePrice($quantity, $unitPrice);
    $discountedPrice = $this->applyDiscount($basePrice, $tier);

    return $this->addTax($discountedPrice, $region);
}
```

## Final Code

Here's the complete `PriceCalculator`:

```php
<?php

namespace App\Pricing;

use TestFlowLabs\TestingAttributes\TestedBy;

class PriceCalculator
{
    #[TestedBy('Tests\PriceCalculatorTest', 'multiplies quantity by unit price')]
    #[TestedBy('Tests\PriceCalculatorTest', 'returns zero for zero quantity')]
    public function calculateBasePrice(int $quantity, int $unitPrice): int
    {
        return $quantity * $unitPrice;
    }

    #[TestedBy('Tests\PriceCalculatorTest', 'applies 20% discount for premium tier')]
    #[TestedBy('Tests\PriceCalculatorTest', 'applies 10% discount for gold tier')]
    #[TestedBy('Tests\PriceCalculatorTest', 'applies no discount for standard tier')]
    public function applyDiscount(int $basePrice, string $tier): int
    {
        $discountRate = match ($tier) {
            'premium' => 0.20,
            'gold' => 0.10,
            default => 0.0,
        };

        return (int) ($basePrice * (1 - $discountRate));
    }

    #[TestedBy('Tests\PriceCalculatorTest', 'adds 8% tax for US region')]
    #[TestedBy('Tests\PriceCalculatorTest', 'adds 20% tax for EU region')]
    public function addTax(int $price, string $region): int
    {
        $taxRate = match ($region) {
            'US' => 0.08,
            'EU' => 0.20,
            default => 0.0,
        };

        return (int) ($price * (1 + $taxRate));
    }

    #[TestedBy('Tests\PriceCalculatorTest', 'combines base price, discount, and tax')]
    #[TestedBy('Tests\PriceCalculatorTest', 'handles standard tier with EU tax')]
    public function calculateTotal(
        int $quantity,
        int $unitPrice,
        string $tier,
        string $region
    ): int {
        $basePrice = $this->calculateBasePrice($quantity, $unitPrice);
        $discountedPrice = $this->applyDiscount($basePrice, $tier);

        return $this->addTax($discountedPrice, $region);
    }
}
```

## Final Validation

```bash
./vendor/bin/testlink validate
```

```
Validation Report
─────────────────

Link Summary
  PHPUnit attribute links: 9
  Pest method chain links: 0
  Total links: 9

TestedBy Summary
  TestedBy attributes found: 9
  Synchronized: 9

✓ All links are valid!
```

## Final Report

```bash
./vendor/bin/testlink report
```

```
Coverage Links Report
─────────────────────

App\Pricing\PriceCalculator

  calculateBasePrice()
    → Tests\PriceCalculatorTest::multiplies quantity by unit price
    → Tests\PriceCalculatorTest::returns zero for zero quantity

  applyDiscount()
    → Tests\PriceCalculatorTest::applies 20% discount for premium tier
    → Tests\PriceCalculatorTest::applies 10% discount for gold tier
    → Tests\PriceCalculatorTest::applies no discount for standard tier

  addTax()
    → Tests\PriceCalculatorTest::adds 8% tax for US region
    → Tests\PriceCalculatorTest::adds 20% tax for EU region

  calculateTotal()
    → Tests\PriceCalculatorTest::combines base price, discount, and tax
    → Tests\PriceCalculatorTest::handles standard tier with EU tax

Summary
  Methods with tests: 4
  Total test links: 9
```

## What You Learned

1. **TDD rhythm** - Red → Green → Refactor with links at each phase
2. **Incremental development** - Build features piece by piece
3. **Link management** - Keep production and test links synchronized
4. **Validation** - Verify link integrity throughout development

## What's Next?

- [BDD Workflow](../bdd/) - Learn behavior-driven development with TestLink
- [Run Validation in CI](/how-to/run-validation-in-ci) - Automate link validation
