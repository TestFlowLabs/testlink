# TDD with TestLink

Test-Driven Development (TDD) follows the **Red-Green-Refactor** cycle. TestLink integrates into this workflow **after** the design emerges from your tests.

## The TDD Cycle

```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│     ┌─────────┐                                         │
│     │   RED   │  Write a failing test                   │
│     │  (FAIL) │  Run tests → See it fail                │
│     └────┬────┘                                         │
│          │                                              │
│          ▼                                              │
│     ┌─────────┐                                         │
│     │  GREEN  │  Write minimum code to pass             │
│     │  (PASS) │  Run tests → See it pass                │
│     └────┬────┘                                         │
│          │                                              │
│          ▼                                              │
│     ┌──────────┐                                        │
│     │ REFACTOR │  Improve code structure                │
│     │  (PASS)  │  Run tests → Still passing             │
│     └────┬─────┘                                        │
│          │                                              │
│          └──────────────── Repeat ──────────────────────┘
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### The Three Rules of TDD

1. **Write a failing test first** - Don't write production code until you have a failing test
2. **Write the minimum code to pass** - Don't write more than necessary to make the test pass
3. **Refactor only when tests pass** - Clean up code while tests are green

### Run Tests Constantly

Every step in TDD involves running tests:

| Step | Action | Expected Result |
|------|--------|-----------------|
| RED | Write test, then run | **FAIL** (test fails) |
| GREEN | Write code, then run | **PASS** (test passes) |
| REFACTOR | Change code, then run | **PASS** (still passes) |

---

## Where Does TestLink Fit?

TestLink is **not** part of the Red-Green-Refactor cycle. It comes **after** your design stabilizes:

```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│   RED → GREEN → REFACTOR → RED → GREEN → REFACTOR →... │
│                                                         │
│   ... → Design Stabilizes → ADD TESTLINK LINKS          │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Why Links Come Last

In true TDD, you don't know the final class or method names until the design emerges. Adding `linksAndCovers(SomeClass::class.'::someMethod')` in the RED phase would mean:

- You're deciding the implementation before writing the test
- You're coupling tests to structure before that structure exists
- You're working against TDD's "let tests drive design" principle

**Links are documentation of what exists, not predictions of what will exist.**

---

## Step-by-Step Example: Building a Price Calculator

Let's build a price calculation feature using strict TDD with TestLink.

### Cycle 1: Basic Tax Calculation

#### RED: Write a Failing Test

Start by describing the behavior you want. Don't think about implementation yet:

::: code-group

```php [Pest]
// tests/Unit/PriceCalculatorTest.php

test('calculates price with tax', function () {
    // We don't know HOW yet, just WHAT we want
    $result = calculate_price_with_tax(100, 0.20);

    expect($result)->toBe(120.0);
});
```

```php [PHPUnit]
// tests/Unit/PriceCalculatorTest.php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    #[Test]
    public function calculates_price_with_tax(): void
    {
        $result = calculate_price_with_tax(100, 0.20);

        $this->assertSame(120.0, $result);
    }
}
```

:::

**Run the test:**

```bash
pest tests/Unit/PriceCalculatorTest.php
```

```
  FAIL  Tests\Unit\PriceCalculatorTest
  ✕ calculates price with tax

  Call to undefined function calculate_price_with_tax()

  Tests:    1 failed
  Duration: 0.05s
```

The test fails because nothing exists yet. **This is exactly what we want.**

#### GREEN: Write Minimum Code to Pass

Write the simplest thing that makes the test pass:

```php
// app/helpers.php

function calculate_price_with_tax(float $price, float $taxRate): float
{
    return $price * (1 + $taxRate);
}
```

**Run the test:**

```bash
pest tests/Unit/PriceCalculatorTest.php
```

```
  PASS  Tests\Unit\PriceCalculatorTest
  ✓ calculates price with tax

  Tests:    1 passed
  Duration: 0.03s
```

**Green!** The simplest solution works.

#### REFACTOR: Nothing to Refactor Yet

With just one simple function, there's nothing to refactor. Move to the next cycle.

---

### Cycle 2: Zero Tax Rate (Edge Case)

#### RED: Add Test for Edge Case

```php
test('handles zero tax rate', function () {
    $result = calculate_price_with_tax(100, 0);

    expect($result)->toBe(100.0);
});
```

**Run tests:**

```bash
pest tests/Unit/PriceCalculatorTest.php
```

```
  PASS  Tests\Unit\PriceCalculatorTest
  ✓ calculates price with tax
  ✓ handles zero tax rate

  Tests:    2 passed
  Duration: 0.03s
```

The test passes immediately! Our implementation already handles this case. No code change needed.

---

### Cycle 3: Negative Price Validation

#### RED: Add Validation Test

```php
test('throws exception for negative price', function () {
    calculate_price_with_tax(-100, 0.20);
})->throws(InvalidArgumentException::class, 'Price cannot be negative');
```

**Run tests:**

```bash
pest tests/Unit/PriceCalculatorTest.php
```

```
  FAIL  Tests\Unit\PriceCalculatorTest
  ✓ calculates price with tax
  ✓ handles zero tax rate
  ✕ throws exception for negative price

  Expected exception [InvalidArgumentException] was not thrown.

  Tests:    1 failed, 2 passed
  Duration: 0.04s
```

#### GREEN: Add Validation

```php
// app/helpers.php

function calculate_price_with_tax(float $price, float $taxRate): float
{
    if ($price < 0) {
        throw new InvalidArgumentException('Price cannot be negative');
    }

    return $price * (1 + $taxRate);
}
```

**Run tests:**

```bash
pest tests/Unit/PriceCalculatorTest.php
```

```
  PASS  Tests\Unit\PriceCalculatorTest
  ✓ calculates price with tax
  ✓ handles zero tax rate
  ✓ throws exception for negative price

  Tests:    3 passed
  Duration: 0.03s
```

---

### Cycle 4: Refactor to Service Class

Now we have three tests. The function is getting complex. Time to refactor to a proper class.

#### REFACTOR: Extract to Service

**First, update the tests to use the new design:**

::: code-group

```php [Pest]
// tests/Unit/PriceCalculatorTest.php

use App\Services\PriceCalculator;

test('calculates price with tax', function () {
    $calculator = new PriceCalculator();

    expect($calculator->withTax(100, 0.20))->toBe(120.0);
});

test('handles zero tax rate', function () {
    $calculator = new PriceCalculator();

    expect($calculator->withTax(100, 0))->toBe(100.0);
});

test('throws exception for negative price', function () {
    $calculator = new PriceCalculator();

    $calculator->withTax(-100, 0.20);
})->throws(InvalidArgumentException::class, 'Price cannot be negative');
```

```php [PHPUnit]
// tests/Unit/PriceCalculatorTest.php

namespace Tests\Unit;

use App\Services\PriceCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    #[Test]
    public function calculates_price_with_tax(): void
    {
        $calculator = new PriceCalculator();

        $this->assertSame(120.0, $calculator->withTax(100, 0.20));
    }

    #[Test]
    public function handles_zero_tax_rate(): void
    {
        $calculator = new PriceCalculator();

        $this->assertSame(100.0, $calculator->withTax(100, 0));
    }

    #[Test]
    public function throws_exception_for_negative_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price cannot be negative');

        $calculator = new PriceCalculator();
        $calculator->withTax(-100, 0.20);
    }
}
```

:::

**Run tests (they fail because class doesn't exist):**

```bash
pest tests/Unit/PriceCalculatorTest.php
```

```
  FAIL  Tests\Unit\PriceCalculatorTest
  ✕ calculates price with tax

  Class "App\Services\PriceCalculator" not found

  Tests:    1 failed
  Duration: 0.04s
```

**Create the service class:**

```php
// app/Services/PriceCalculator.php

namespace App\Services;

use InvalidArgumentException;

class PriceCalculator
{
    public function withTax(float $price, float $taxRate): float
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Price cannot be negative');
        }

        return $price * (1 + $taxRate);
    }
}
```

**Run tests:**

```bash
pest tests/Unit/PriceCalculatorTest.php
```

```
  PASS  Tests\Unit\PriceCalculatorTest
  ✓ calculates price with tax
  ✓ handles zero tax rate
  ✓ throws exception for negative price

  Tests:    3 passed
  Duration: 0.03s
```

**Refactoring complete!** Same behavior, better structure.

---

### Cycle 5: New Feature - Discount Calculation

#### RED: Write Test for Discount

```php
test('applies discount before tax', function () {
    $calculator = new PriceCalculator();

    // 100 - 10% discount = 90, then + 20% tax = 108
    expect($calculator->withDiscountAndTax(100, 0.10, 0.20))->toBe(108.0);
});
```

**Run tests:**

```bash
pest tests/Unit/PriceCalculatorTest.php
```

```
  FAIL  Tests\Unit\PriceCalculatorTest
  ✓ calculates price with tax
  ✓ handles zero tax rate
  ✓ throws exception for negative price
  ✕ applies discount before tax

  Call to undefined method App\Services\PriceCalculator::withDiscountAndTax()

  Tests:    1 failed, 3 passed
  Duration: 0.04s
```

#### GREEN: Add Discount Method

```php
// app/Services/PriceCalculator.php

public function withDiscountAndTax(float $price, float $discount, float $taxRate): float
{
    if ($price < 0) {
        throw new InvalidArgumentException('Price cannot be negative');
    }

    $discountedPrice = $price * (1 - $discount);

    return $discountedPrice * (1 + $taxRate);
}
```

**Run tests:**

```bash
pest tests/Unit/PriceCalculatorTest.php
```

```
  PASS  Tests\Unit\PriceCalculatorTest
  ✓ calculates price with tax
  ✓ handles zero tax rate
  ✓ throws exception for negative price
  ✓ applies discount before tax

  Tests:    4 passed
  Duration: 0.03s
```

---

## Design Stabilizes: Add TestLink

After several TDD cycles, our design is stable:
- `PriceCalculator` class with `withTax()` and `withDiscountAndTax()` methods
- Clear validation rules
- Well-tested behavior

**Now** it's time to add bidirectional links.

### Add Links to Tests

::: code-group

```php [Pest]
// tests/Unit/PriceCalculatorTest.php

use App\Services\PriceCalculator;

test('calculates price with tax', function () {
    $calculator = new PriceCalculator();

    expect($calculator->withTax(100, 0.20))->toBe(120.0);
})->linksAndCovers(PriceCalculator::class.'::withTax');

test('handles zero tax rate', function () {
    $calculator = new PriceCalculator();

    expect($calculator->withTax(100, 0))->toBe(100.0);
})->linksAndCovers(PriceCalculator::class.'::withTax');

test('throws exception for negative price', function () {
    $calculator = new PriceCalculator();

    $calculator->withTax(-100, 0.20);
})->throws(InvalidArgumentException::class, 'Price cannot be negative')
  ->linksAndCovers(PriceCalculator::class.'::withTax');

test('applies discount before tax', function () {
    $calculator = new PriceCalculator();

    expect($calculator->withDiscountAndTax(100, 0.10, 0.20))->toBe(108.0);
})->linksAndCovers(PriceCalculator::class.'::withDiscountAndTax');
```

```php [PHPUnit]
// tests/Unit/PriceCalculatorTest.php

namespace Tests\Unit;

use App\Services\PriceCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class PriceCalculatorTest extends TestCase
{
    #[Test]
    #[LinksAndCovers(PriceCalculator::class, 'withTax')]
    public function calculates_price_with_tax(): void
    {
        $calculator = new PriceCalculator();

        $this->assertSame(120.0, $calculator->withTax(100, 0.20));
    }

    #[Test]
    #[LinksAndCovers(PriceCalculator::class, 'withTax')]
    public function handles_zero_tax_rate(): void
    {
        $calculator = new PriceCalculator();

        $this->assertSame(100.0, $calculator->withTax(100, 0));
    }

    #[Test]
    #[LinksAndCovers(PriceCalculator::class, 'withTax')]
    public function throws_exception_for_negative_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price cannot be negative');

        $calculator = new PriceCalculator();
        $calculator->withTax(-100, 0.20);
    }

    #[Test]
    #[LinksAndCovers(PriceCalculator::class, 'withDiscountAndTax')]
    public function applies_discount_before_tax(): void
    {
        $calculator = new PriceCalculator();

        $this->assertSame(108.0, $calculator->withDiscountAndTax(100, 0.10, 0.20));
    }
}
```

:::

### Add Links to Production Code

```php
// app/Services/PriceCalculator.php

namespace App\Services;

use InvalidArgumentException;
use Tests\Unit\PriceCalculatorTest;
use TestFlowLabs\TestingAttributes\TestedBy;

class PriceCalculator
{
    #[TestedBy(PriceCalculatorTest::class, 'calculates price with tax')]
    #[TestedBy(PriceCalculatorTest::class, 'handles zero tax rate')]
    #[TestedBy(PriceCalculatorTest::class, 'throws exception for negative price')]
    public function withTax(float $price, float $taxRate): float
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Price cannot be negative');
        }

        return $price * (1 + $taxRate);
    }

    #[TestedBy(PriceCalculatorTest::class, 'applies discount before tax')]
    public function withDiscountAndTax(float $price, float $discount, float $taxRate): float
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Price cannot be negative');
        }

        $discountedPrice = $price * (1 - $discount);

        return $discountedPrice * (1 + $taxRate);
    }
}
```

### Validate Links

```bash
testlink validate
```

```
  Validation Report
  ─────────────────

  Link Summary
  ────────────

    PHPUnit attribute links: 0
    Pest method chain links: 4
    Total links: 4

  ✓ All links are valid!
```

---

## Using Placeholders for Faster Iteration

During rapid TDD cycles, writing full class references can slow you down. **Placeholders** let you establish links quickly, then resolve them when design stabilizes.

### During TDD (with Placeholders)

::: code-group

```php [Test - Pest]
test('calculates price with tax', function () {
    $calculator = new PriceCalculator();

    expect($calculator->withTax(100, 0.20))->toBe(120.0);
})->linksAndCovers('@price');
```

```php [Production]
#[TestedBy('@price')]
public function withTax(float $price, float $taxRate): float
{
    // ...
}
```

:::

### Resolve When Design Stabilizes

```bash
# Preview changes
testlink pair --dry-run

# Apply changes
testlink pair
```

See the [Placeholder Pairing Guide](/guide/placeholder-pairing) for complete documentation.

---

## When to Add Links

| TDD Phase | Focus | Add Links? |
|-----------|-------|------------|
| **RED** | Describe behavior | No - design unknown |
| **GREEN** | Make it work | No - design evolving |
| **REFACTOR** | Make it right | No - design may change |
| **STABLE** | Design is final | **Yes** - document it |

### Signs Your Design is Stable

- Class and method names are final
- Public API is unlikely to change
- You're ready to commit
- You've completed several TDD cycles without major restructuring

---

## Key Principles

1. **TDD drives design** - Write tests first, let implementation emerge
2. **Run tests constantly** - Every step involves running tests
3. **Take the minimum next step** - Don't over-engineer
4. **Links document stable design** - Add them after design emerges
5. **Validation ensures accuracy** - Keep links synchronized over time

The power of TestLink in TDD is not in the RED phase - it's in maintaining accurate documentation of your test coverage as your codebase evolves.

::: tip CI Integration
To enforce link validation in your CI/CD pipeline, see the [CI Integration Guide](/best-practices/ci-integration).
:::
