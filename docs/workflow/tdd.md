# TDD with TestLink

Test-Driven Development (TDD) follows the Red-Green-Refactor cycle. TestLink integrates into this workflow **after** the design emerges from your tests.

## The TDD Cycle with TestLink

```
┌─────────────────────────────────────────────────────────┐
│  1. RED: Write failing test (NO links yet)              │
│     ↓                                                   │
│  2. GREEN: Write minimal production code                │
│     ↓                                                   │
│  3. REFACTOR: Clean up code                             │
│     ↓                                                   │
│  4. LINK: Add #[TestedBy] and linksAndCovers()          │
│     ↓                                                   │
│  5. VALIDATE: Run testlink validate                     │
└─────────────────────────────────────────────────────────┘
```

## Why Links Come Last

In true TDD, you don't know the final class or method names until the design emerges from your tests. Adding `linksAndCovers(SomeClass::class.'::someMethod')` in the RED phase would mean:

- You're deciding the implementation before writing the test
- You're coupling tests to structure before that structure exists
- You're working against TDD's "let tests drive design" principle

**Links are documentation of what exists, not predictions of what will exist.**

## Step-by-Step Example

Let's build a price calculation feature using TDD with TestLink.

### Step 1: RED - Write the Failing Test

Start by describing the behavior you want. Focus only on the behavior, not on how it will be implemented:

::: code-group

```php [Pest]
// tests/Unit/PriceCalculationTest.php

test('calculates total with tax', function () {
    // At this point, we don't know if this will be:
    // - A PriceCalculator class
    // - A Cart method
    // - A static helper
    // - Something else entirely

    // We just describe what we want:
    $total = calculate_price_with_tax(100, 0.20);

    expect($total)->toBe(120.0);
});
```

```php [PHPUnit]
// tests/Unit/PriceCalculationTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PriceCalculationTest extends TestCase
{
    public function test_calculates_total_with_tax(): void
    {
        // We don't know the implementation yet
        // Just describe the desired behavior
        $total = calculate_price_with_tax(100, 0.20);

        $this->assertSame(120.0, $total);
    }
}
```

:::

Run the test - it fails because nothing exists yet. **This is exactly what we want.**

### Step 2: GREEN - Write Minimal Production Code

Now make the simplest thing that works. The design emerges:

```php
// app/helpers.php (simplest solution first)

function calculate_price_with_tax(float $price, float $taxRate): float
{
    return $price * (1 + $taxRate);
}
```

Run the test - it passes.

### Step 3: REFACTOR - Improve the Design

Maybe you realize this belongs in a service class:

```php
// app/Services/PriceCalculator.php

namespace App\Services;

class PriceCalculator
{
    public function withTax(float $price, float $taxRate): float
    {
        return $price * (1 + $taxRate);
    }
}
```

Update the test to use the new design:

::: code-group

```php [Pest]
use App\Services\PriceCalculator;

test('calculates total with tax', function () {
    $calculator = new PriceCalculator();

    expect($calculator->withTax(100, 0.20))->toBe(120.0);
});
```

```php [PHPUnit]
use App\Services\PriceCalculator;

class PriceCalculationTest extends TestCase
{
    public function test_calculates_total_with_tax(): void
    {
        $calculator = new PriceCalculator();

        $this->assertSame(120.0, $calculator->withTax(100, 0.20));
    }
}
```

:::

### Step 4: LINK - Add Traceability

**Now** that the design is stable, add the bidirectional links:

::: code-group

```php [Test - Pest]
use App\Services\PriceCalculator;

test('calculates total with tax', function () {
    $calculator = new PriceCalculator();

    expect($calculator->withTax(100, 0.20))->toBe(120.0);
})->linksAndCovers(PriceCalculator::class.'::withTax');  // Added after design is stable
```

```php [Test - PHPUnit]
use App\Services\PriceCalculator;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class PriceCalculationTest extends TestCase
{
    #[LinksAndCovers(PriceCalculator::class, 'withTax')]  // Added after design is stable
    public function test_calculates_total_with_tax(): void
    {
        $calculator = new PriceCalculator();

        $this->assertSame(120.0, $calculator->withTax(100, 0.20));
    }
}
```

```php [Production]
namespace App\Services;

use Tests\Unit\PriceCalculationTest;
use TestFlowLabs\TestingAttributes\TestedBy;

class PriceCalculator
{
    #[TestedBy(PriceCalculationTest::class, 'test_calculates_total_with_tax')]
    public function withTax(float $price, float $taxRate): float
    {
        return $price * (1 + $taxRate);
    }
}
```

:::

### Step 5: VALIDATE - Ensure Sync

```bash
testlink validate
```

```
Validation Report:
  ✓ All links are synchronized!

  Bidirectional links: 1
```

## When to Add Links

| Phase | Focus | Links? |
|-------|-------|--------|
| **RED** | Describe behavior | No - design unknown |
| **GREEN** | Make it work | No - design still evolving |
| **REFACTOR** | Make it right | No - design may still change |
| **STABLE** | Design is final | Yes - now document it |

## Multiple TDD Cycles, Then Link

You might go through several Red-Green-Refactor cycles before the design stabilizes:

```
RED → GREEN → REFACTOR → RED → GREEN → REFACTOR → ... → STABLE → LINK
```

Don't add links after every cycle. Wait until:
- The class/method names are stable
- The public API is unlikely to change
- You're ready to commit

## Batch Linking with Auto-Sync

If you've written many tests before adding links, use auto-sync:

```bash
# First, add #[TestedBy] attributes to production code
# Then generate test links automatically:

testlink sync --dry-run  # Preview
testlink sync            # Apply
```

## CI Integration

Add validation to catch sync issues before merging:

```yaml
# .github/workflows/test.yml
- name: Validate coverage links
  run: testlink validate --strict
```

## Summary

TestLink complements TDD but doesn't change it:

1. **TDD drives design** - Write tests first, let implementation emerge
2. **Links document design** - Add them after the design is stable
3. **Validation enforces sync** - Ensure links stay accurate over time

The power of TestLink in TDD is not in the RED phase - it's in maintaining accurate documentation of your test coverage as your codebase evolves.
