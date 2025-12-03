# Quick Start

This guide walks you through setting up test coverage links in 5 minutes.

## Step 1: Create a Test with Coverage Link

Link your test to the production method it covers:

::: code-group

```php [Pest]
<?php

// tests/Unit/CalculatorTest.php

use App\Services\Calculator;

test('adds two numbers', function () {
    $calculator = new Calculator();

    expect($calculator->add(2, 3))->toBe(5);
})->linksAndCovers(Calculator::class.'::add');
```

```php [PHPUnit]
<?php

// tests/Unit/CalculatorTest.php

namespace Tests\Unit;

use App\Services\Calculator;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class CalculatorTest extends TestCase
{
    #[LinksAndCovers(Calculator::class, 'add')]
    public function test_adds_two_numbers(): void
    {
        $calculator = new Calculator();

        $this->assertSame(5, $calculator->add(2, 3));
    }
}
```

:::

## Step 2: View Coverage Report

Generate a coverage links report:

```bash
testlink report
```

Output:

```
Coverage Links Report
─────────────────────

App\Services\Calculator::add
  → Tests\Unit\CalculatorTest::test_adds_two_numbers

Summary:
  Methods: 1
  Tests: 1
```

## Step 3: Validate Links

Run validation to ensure links are valid:

```bash
testlink validate
```

Expected output:

```
Validation Report:
  ✓ All links are valid!

  PHPUnit attribute links: 1
  Pest method chain links: 0
  Total links: 1
```

## Adding Multiple Links

### Multiple Tests for One Method

::: code-group

```php [Pest]
test('creates a user', function () {
    // Test implementation
})->linksAndCovers(UserService::class.'::create');

test('validates email', function () {
    // Test implementation
})->linksAndCovers(UserService::class.'::create');
```

```php [PHPUnit]
class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_a_user(): void
    {
        // Test implementation
    }

    #[LinksAndCovers(UserService::class, 'create')]
    public function test_validates_email(): void
    {
        // Test implementation
    }
}
```

:::

### One Test Covering Multiple Methods

::: code-group

```php [Pest]
test('complete checkout flow', function () {
    // Test implementation
})->linksAndCovers(CartService::class.'::checkout')
  ->linksAndCovers(PaymentService::class.'::charge')
  ->linksAndCovers(OrderService::class.'::create');
```

```php [PHPUnit]
#[LinksAndCovers(CartService::class, 'checkout')]
#[LinksAndCovers(PaymentService::class, 'charge')]
#[LinksAndCovers(OrderService::class, 'create')]
public function test_complete_checkout_flow(): void
{
    // Test implementation
}
```

:::
