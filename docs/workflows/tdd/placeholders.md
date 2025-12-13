# Placeholder TDD

This tutorial teaches you how to use placeholder markers for rapid TDD iteration. Placeholders let you defer the naming decision until you're ready.

## The Problem with Immediate Linking

During TDD, you often don't know the final class or method name until you're deep into implementation. Standard linking requires:

```php
// You need to know the exact class name before it exists!
->linksAndCovers(PriceCalculator::class.'::calculateDiscount')
```

This creates friction during the exploratory phase of TDD.

## Placeholders to the Rescue

Placeholders let you use temporary markers:

```php
// Production code
#[TestedBy('@discount')]

// Test code
->linksAndCovers('@discount')
```

Later, run `testlink pair` to replace placeholders with real references.

## Placeholder Syntax

Valid placeholder formats:
- `@A`, `@B`, `@C` - Single letters for quick iteration
- `@feature-name` - Descriptive names
- `@UserCreate` - PascalCase
- `@user_update` - snake_case

Must start with `@` followed by a letter.

## Tutorial: Building a Discount Calculator

Let's build a discount calculator using placeholder TDD.

### Step 1: Start with a Placeholder Test

:::tabs key:stack
== Pest

```php
<?php
// tests/DiscountTest.php

test('premium users get 20% discount', function () {
    // We don't know the class name yet!
    $discount = calculateDiscount('premium', 100);

    expect($discount)->toBe(20);
})->linksAndCovers('@premium-discount');
```

== PHPUnit + Attributes

```php
<?php
// tests/DiscountTest.php

namespace Tests;

use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class DiscountTest extends TestCase
{
    #[LinksAndCovers('@premium-discount')]
    public function test_premium_users_get_20_percent_discount(): void
    {
        $discount = calculateDiscount('premium', 100);

        $this->assertSame(20, $discount);
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/DiscountTest.php

namespace Tests;

use PHPUnit\Framework\TestCase;

class DiscountTest extends TestCase
{
    /**
     * @see @premium-discount
     */
    public function test_premium_users_get_20_percent_discount(): void
    {
        $discount = calculateDiscount('premium', 100);

        $this->assertSame(20, $discount);
    }
}
```

::: tip
For @see tags, placeholders are resolved to real class references after running `testlink pair`.
:::

:::

### Step 2: Write Production Code with Matching Placeholder

As you implement, add the matching placeholder:

```php
<?php
// src/Pricing/DiscountCalculator.php

namespace App\Pricing;

use TestFlowLabs\TestingAttributes\TestedBy;

class DiscountCalculator
{
    #[TestedBy('@premium-discount')]
    public function calculateForUserType(string $type, int $amount): int
    {
        return match ($type) {
            'premium' => (int) ($amount * 0.20),
            'gold' => (int) ($amount * 0.15),
            'silver' => (int) ($amount * 0.10),
            default => 0,
        };
    }
}
```

### Step 3: Add More Tests with Same Placeholder

Multiple tests can use the same placeholder:

:::tabs key:stack
== Pest

```php
test('gold users get 15% discount', function () {
    $calculator = new DiscountCalculator();

    expect($calculator->calculateForUserType('gold', 100))->toBe(15);
})->linksAndCovers('@premium-discount');

test('regular users get no discount', function () {
    $calculator = new DiscountCalculator();

    expect($calculator->calculateForUserType('regular', 100))->toBe(0);
})->linksAndCovers('@premium-discount');
```

== PHPUnit + Attributes

```php
#[LinksAndCovers('@premium-discount')]
public function test_gold_users_get_15_percent_discount(): void
{
    $calculator = new DiscountCalculator();

    $this->assertSame(15, $calculator->calculateForUserType('gold', 100));
}

#[LinksAndCovers('@premium-discount')]
public function test_regular_users_get_no_discount(): void
{
    $calculator = new DiscountCalculator();

    $this->assertSame(0, $calculator->calculateForUserType('regular', 100));
}
```

== PHPUnit + @see

```php
/**
 * @see @premium-discount
 */
public function test_gold_users_get_15_percent_discount(): void
{
    $calculator = new DiscountCalculator();

    $this->assertSame(15, $calculator->calculateForUserType('gold', 100));
}

/**
 * @see @premium-discount
 */
public function test_regular_users_get_no_discount(): void
{
    $calculator = new DiscountCalculator();

    $this->assertSame(0, $calculator->calculateForUserType('regular', 100));
}
```

:::

### Step 4: Preview the Pairing

Before resolving, preview what will happen:

```bash
./vendor/bin/testlink pair --dry-run
```

```
Pairing Placeholders
────────────────────
Running in dry-run mode. No files will be modified.

Found Placeholders
  ✓ @premium-discount  1 production × 3 tests = 3 links

Production Files
  src/Pricing/DiscountCalculator.php
    @premium-discount → Tests\DiscountTest::test_premium_users_get_20_percent_discount
    @premium-discount → Tests\DiscountTest::test_gold_users_get_15_percent_discount
    @premium-discount → Tests\DiscountTest::test_regular_users_get_no_discount

Test Files
  tests/DiscountTest.php
    @premium-discount → App\Pricing\DiscountCalculator::calculateForUserType

Dry run complete. Would modify 2 file(s) with 4 change(s).
```

### Step 5: Resolve Placeholders

When you're happy with the names, resolve the placeholders:

```bash
./vendor/bin/testlink pair
```

```
Pairing Placeholders
────────────────────

Resolving @premium-discount...
  ✓ Updated src/Pricing/DiscountCalculator.php
  ✓ Updated tests/DiscountTest.php

Summary
  Resolved: 1 placeholder(s)
  Modified: 2 file(s)
  Changes: 4
```

### Step 6: Verify the Result

Your production code now has real links:

```php
<?php
// src/Pricing/DiscountCalculator.php

namespace App\Pricing;

use TestFlowLabs\TestingAttributes\TestedBy;

class DiscountCalculator
{
    #[TestedBy('Tests\DiscountTest', 'test_premium_users_get_20_percent_discount')]
    #[TestedBy('Tests\DiscountTest', 'test_gold_users_get_15_percent_discount')]
    #[TestedBy('Tests\DiscountTest', 'test_regular_users_get_no_discount')]
    public function calculateForUserType(string $type, int $amount): int
    {
        return match ($type) {
            'premium' => (int) ($amount * 0.20),
            'gold' => (int) ($amount * 0.15),
            'silver' => (int) ($amount * 0.10),
            default => 0,
        };
    }
}
```

And your tests have real links:

:::tabs key:stack
== Pest

```php
test('premium users get 20% discount', function () {
    $calculator = new DiscountCalculator();

    expect($calculator->calculateForUserType('premium', 100))->toBe(20);
})->linksAndCovers(DiscountCalculator::class.'::calculateForUserType');
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(DiscountCalculator::class, 'calculateForUserType')]
public function test_premium_users_get_20_percent_discount(): void
{
    $calculator = new DiscountCalculator();

    $this->assertSame(20, $calculator->calculateForUserType('premium', 100));
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Pricing\DiscountCalculator::calculateForUserType
 */
public function test_premium_users_get_20_percent_discount(): void
{
    $calculator = new DiscountCalculator();

    $this->assertSame(20, $calculator->calculateForUserType('premium', 100));
}
```

:::

## N:M Placeholder Matching

Placeholders support N:M relationships—multiple production methods linked to multiple tests.

### Example: Multiple Methods

```php
// Production code
class OrderService
{
    #[TestedBy('@order-flow')]
    public function create(array $data): Order { }

    #[TestedBy('@order-flow')]
    public function validate(Order $order): bool { }
}

// Test code
test('creates valid order', function () { })
    ->linksAndCovers('@order-flow');

test('validates order data', function () { })
    ->linksAndCovers('@order-flow');
```

Running `pair` creates:
- 2 production methods × 2 tests = 4 links

## Placeholder Workflow Tips

### Use Short Placeholders for Exploration

```php
// Quick iteration
#[TestedBy('@A')]
->linksAndCovers('@A')

// Later, rename to something meaningful
#[TestedBy('@user-registration')]
->linksAndCovers('@user-registration')
```

### Use Descriptive Placeholders for Features

```php
// Feature-based placeholders
#[TestedBy('@checkout-total')]
#[TestedBy('@checkout-tax')]
#[TestedBy('@checkout-shipping')]
```

### Resolve Placeholders Before Committing

```bash
# Check for unresolved placeholders
./vendor/bin/testlink validate

# Resolve all placeholders
./vendor/bin/testlink pair
```

## What's Next?

- [Complete Example](./complete-example) - Full TDD example from start to finish
- [Handle N:M Relationships](/how-to/handle-nm-relationships) - Advanced placeholder patterns
