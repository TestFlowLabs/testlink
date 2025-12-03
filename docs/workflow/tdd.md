# TDD with TestLink

Test-Driven Development (TDD) follows the Red-Green-Refactor cycle. TestLink integrates naturally into this workflow by adding traceability at the right moment.

## The TDD Cycle with TestLink

```
┌─────────────────────────────────────────────────────────┐
│  1. RED: Write failing test with linksAndCovers()       │
│     ↓                                                   │
│  2. GREEN: Write minimal production code                │
│     ↓                                                   │
│  3. REFACTOR: Clean up, add #[TestedBy] to production   │
│     ↓                                                   │
│  4. VALIDATE: Run testlink validate                     │
└─────────────────────────────────────────────────────────┘
```

## Step-by-Step Example

Let's build a `PriceCalculator` service using TDD with TestLink.

### Step 1: RED - Write the Failing Test

Start by writing a test that describes the behavior you want. Add the coverage link immediately:

::: code-group

```php [Pest]
// tests/Unit/PriceCalculatorTest.php

use App\Services\PriceCalculator;

test('calculates total with tax', function () {
    $calculator = new PriceCalculator();

    $total = $calculator->calculateWithTax(100, 0.20);

    expect($total)->toBe(120.0);
})->linksAndCovers(PriceCalculator::class.'::calculateWithTax');
```

```php [PHPUnit]
// tests/Unit/PriceCalculatorTest.php

namespace Tests\Unit;

use App\Services\PriceCalculator;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class PriceCalculatorTest extends TestCase
{
    #[LinksAndCovers(PriceCalculator::class, 'calculateWithTax')]
    public function test_calculates_total_with_tax(): void
    {
        $calculator = new PriceCalculator();

        $total = $calculator->calculateWithTax(100, 0.20);

        $this->assertSame(120.0, $total);
    }
}
```

:::

Run the test - it fails because `PriceCalculator` doesn't exist yet. This is expected.

### Step 2: GREEN - Write Minimal Production Code

Create the production class with just enough code to make the test pass:

```php
// app/Services/PriceCalculator.php

namespace App\Services;

class PriceCalculator
{
    public function calculateWithTax(float $price, float $taxRate): float
    {
        return $price * (1 + $taxRate);
    }
}
```

Run the test again - it passes.

### Step 3: REFACTOR - Add TestedBy Attribute

Now that the code works, add the `#[TestedBy]` attribute to document the bidirectional link:

```php
// app/Services/PriceCalculator.php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;

class PriceCalculator
{
    #[TestedBy(PriceCalculatorTest::class, 'test_calculates_total_with_tax')]
    public function calculateWithTax(float $price, float $taxRate): float
    {
        return $price * (1 + $taxRate);
    }
}
```

### Step 4: VALIDATE - Ensure Sync

Run validation to confirm bidirectional links are in sync:

```bash
testlink validate
```

```
Validation Report:
  ✓ All links are synchronized!

  Bidirectional links: 1
```

## Adding More Tests

As you add more test cases for the same method, update both sides:

::: code-group

```php [Test - Pest]
test('calculates total with tax', function () {
    $calculator = new PriceCalculator();
    expect($calculator->calculateWithTax(100, 0.20))->toBe(120.0);
})->linksAndCovers(PriceCalculator::class.'::calculateWithTax');

test('handles zero tax rate', function () {
    $calculator = new PriceCalculator();
    expect($calculator->calculateWithTax(100, 0))->toBe(100.0);
})->linksAndCovers(PriceCalculator::class.'::calculateWithTax');

test('handles negative prices', function () {
    $calculator = new PriceCalculator();
    expect($calculator->calculateWithTax(-50, 0.10))->toBe(-55.0);
})->linksAndCovers(PriceCalculator::class.'::calculateWithTax');
```

```php [Production]
use TestFlowLabs\TestingAttributes\TestedBy;

class PriceCalculator
{
    #[TestedBy(PriceCalculatorTest::class, 'test_calculates_total_with_tax')]
    #[TestedBy(PriceCalculatorTest::class, 'test_handles_zero_tax_rate')]
    #[TestedBy(PriceCalculatorTest::class, 'test_handles_negative_prices')]
    public function calculateWithTax(float $price, float $taxRate): float
    {
        return $price * (1 + $taxRate);
    }
}
```

:::

## When to Add Links

| Phase | Action |
|-------|--------|
| **RED** | Add `linksAndCovers()` or `#[LinksAndCovers]` to test |
| **GREEN** | Focus on making test pass, no links yet |
| **REFACTOR** | Add `#[TestedBy]` to production code |

## Auto-Sync for Efficiency

If you prefer to add `#[TestedBy]` first and generate test links automatically:

```bash
# After adding #[TestedBy] to production code
testlink sync --dry-run  # Preview changes
testlink sync            # Apply changes
```

This adds the corresponding `linksAndCovers()` calls to your test files.

## CI Integration

Add validation to your CI pipeline to catch sync issues:

```yaml
# .github/workflows/test.yml
- name: Validate coverage links
  run: testlink validate --strict
```

## Benefits in TDD

1. **Documentation at the source**: `#[TestedBy]` shows which tests cover each method
2. **Refactoring confidence**: When renaming methods, validation catches broken links
3. **Coverage visibility**: `testlink report` shows exactly what's tested
4. **Sync enforcement**: CI validation prevents untested code from merging
