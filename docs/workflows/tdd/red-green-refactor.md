# Red-Green-Refactor with TestLink

This tutorial walks you through the classic TDD cycle—Red, Green, Refactor—while integrating TestLink at each phase.

## What You'll Build

We'll build a `StringCalculator` class that adds numbers from a string input. This is a classic TDD kata.

## Prerequisites

- Completed [Getting Started](../getting-started)
- TestLink installed in your project

## The TDD Cycle with Links

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│    ┌─────────┐      ┌─────────┐      ┌───────────┐         │
│    │  RED    │ ───→ │  GREEN  │ ───→ │ REFACTOR  │         │
│    │         │      │         │      │           │         │
│    │ + test  │      │ + code  │      │ + cleanup │         │
│    │ + link  │      │ + link  │      │ ± links   │         │
│    └─────────┘      └─────────┘      └───────────┘         │
│         ↑                                   │               │
│         └───────────────────────────────────┘               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Iteration 1: Empty String Returns Zero

### Red Phase: Write a Failing Test

Create the test file and write your first test:

:::tabs key:stack
== Pest

```php
<?php
// tests/StringCalculatorTest.php

use App\StringCalculator;

test('returns zero for empty string', function () {
    $calculator = new StringCalculator();

    expect($calculator->add(''))->toBe(0);
})->linksAndCovers(StringCalculator::class.'::add');
```

== PHPUnit + Attributes

```php
<?php
// tests/StringCalculatorTest.php

namespace Tests;

use App\StringCalculator;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class StringCalculatorTest extends TestCase
{
    #[LinksAndCovers(StringCalculator::class, 'add')]
    public function test_returns_zero_for_empty_string(): void
    {
        $calculator = new StringCalculator();

        $this->assertSame(0, $calculator->add(''));
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/StringCalculatorTest.php

namespace Tests;

use App\StringCalculator;
use PHPUnit\Framework\TestCase;

class StringCalculatorTest extends TestCase
{
    /**
     * @see \App\StringCalculator::add
     */
    public function test_returns_zero_for_empty_string(): void
    {
        $calculator = new StringCalculator();

        $this->assertSame(0, $calculator->add(''));
    }
}
```

:::

Run the test—it fails because `StringCalculator` doesn't exist:

```bash
./vendor/bin/pest
# Error: Class "App\StringCalculator" not found
```

### Green Phase: Make It Pass

Create the minimal production code:

```php
<?php
// src/StringCalculator.php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class StringCalculator
{
    #[TestedBy('Tests\StringCalculatorTest', 'returns zero for empty string')]
    public function add(string $numbers): int
    {
        return 0; // Minimal implementation
    }
}
```

Run the test—it passes:

```bash
./vendor/bin/pest
# ✓ returns zero for empty string
```

Validate the links:

```bash
./vendor/bin/testlink validate
# ✓ All links are valid!
```

### Refactor Phase

No refactoring needed yet. The code is minimal.

## Iteration 2: Single Number Returns Itself

### Red Phase

Add a new test:

:::tabs key:stack
== Pest

```php
test('returns the number for single number', function () {
    $calculator = new StringCalculator();

    expect($calculator->add('5'))->toBe(5);
})->linksAndCovers(StringCalculator::class.'::add');
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(StringCalculator::class, 'add')]
public function test_returns_the_number_for_single_number(): void
{
    $calculator = new StringCalculator();

    $this->assertSame(5, $calculator->add('5'));
}
```

== PHPUnit + @see

```php
/**
 * @see \App\StringCalculator::add
 */
public function test_returns_the_number_for_single_number(): void
{
    $calculator = new StringCalculator();

    $this->assertSame(5, $calculator->add('5'));
}
```

:::

Run tests—the new one fails:

```bash
./vendor/bin/pest
# ✓ returns zero for empty string
# ✗ returns the number for single number
```

### Green Phase

Update the production code:

```php
<?php
// src/StringCalculator.php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class StringCalculator
{
    #[TestedBy('Tests\StringCalculatorTest', 'returns zero for empty string')]
    #[TestedBy('Tests\StringCalculatorTest', 'returns the number for single number')]
    public function add(string $numbers): int
    {
        if ($numbers === '') {
            return 0;
        }

        return (int) $numbers;
    }
}
```

Run tests—both pass:

```bash
./vendor/bin/pest
# ✓ returns zero for empty string
# ✓ returns the number for single number
```

### Refactor Phase

No refactoring needed yet.

## Iteration 3: Two Numbers

### Red Phase

:::tabs key:stack
== Pest

```php
test('returns sum of two numbers', function () {
    $calculator = new StringCalculator();

    expect($calculator->add('1,2'))->toBe(3);
})->linksAndCovers(StringCalculator::class.'::add');
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(StringCalculator::class, 'add')]
public function test_returns_sum_of_two_numbers(): void
{
    $calculator = new StringCalculator();

    $this->assertSame(3, $calculator->add('1,2'));
}
```

== PHPUnit + @see

```php
/**
 * @see \App\StringCalculator::add
 */
public function test_returns_sum_of_two_numbers(): void
{
    $calculator = new StringCalculator();

    $this->assertSame(3, $calculator->add('1,2'));
}
```

:::

### Green Phase

```php
<?php
// src/StringCalculator.php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class StringCalculator
{
    #[TestedBy('Tests\StringCalculatorTest', 'returns zero for empty string')]
    #[TestedBy('Tests\StringCalculatorTest', 'returns the number for single number')]
    #[TestedBy('Tests\StringCalculatorTest', 'returns sum of two numbers')]
    public function add(string $numbers): int
    {
        if ($numbers === '') {
            return 0;
        }

        $parts = explode(',', $numbers);

        return array_sum(array_map('intval', $parts));
    }
}
```

### Refactor Phase

The code now handles the general case. Previous implementations were stepping stones.

## Iteration 4: Multiple Numbers

### Red Phase

:::tabs key:stack
== Pest

```php
test('returns sum of multiple numbers', function () {
    $calculator = new StringCalculator();

    expect($calculator->add('1,2,3,4,5'))->toBe(15);
})->linksAndCovers(StringCalculator::class.'::add');
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(StringCalculator::class, 'add')]
public function test_returns_sum_of_multiple_numbers(): void
{
    $calculator = new StringCalculator();

    $this->assertSame(15, $calculator->add('1,2,3,4,5'));
}
```

== PHPUnit + @see

```php
/**
 * @see \App\StringCalculator::add
 */
public function test_returns_sum_of_multiple_numbers(): void
{
    $calculator = new StringCalculator();

    $this->assertSame(15, $calculator->add('1,2,3,4,5'));
}
```

:::

### Green Phase

The test passes immediately! Our implementation already handles this case.

```bash
./vendor/bin/pest
# ✓ returns zero for empty string
# ✓ returns the number for single number
# ✓ returns sum of two numbers
# ✓ returns sum of multiple numbers
```

Add the `#[TestedBy]` attribute anyway:

```php
#[TestedBy('Tests\StringCalculatorTest', 'returns zero for empty string')]
#[TestedBy('Tests\StringCalculatorTest', 'returns the number for single number')]
#[TestedBy('Tests\StringCalculatorTest', 'returns sum of two numbers')]
#[TestedBy('Tests\StringCalculatorTest', 'returns sum of multiple numbers')]
public function add(string $numbers): int
```

## Final Validation

Run a complete validation:

```bash
./vendor/bin/testlink validate
```

```
Validation Report
─────────────────

Link Summary
  PHPUnit attribute links: 4
  Pest method chain links: 0
  Total links: 4

TestedBy Summary
  TestedBy attributes found: 4
  Synchronized: 4

✓ All links are valid!
```

## View the Report

```bash
./vendor/bin/testlink report
```

```
Coverage Links Report
─────────────────────

App\StringCalculator

  add()
    → Tests\StringCalculatorTest::returns zero for empty string
    → Tests\StringCalculatorTest::returns the number for single number
    → Tests\StringCalculatorTest::returns sum of two numbers
    → Tests\StringCalculatorTest::returns sum of multiple numbers

Summary
  Methods with tests: 1
  Total test links: 4
```

## Key Takeaways

1. **Add test-side links in Red phase** - When writing the failing test
2. **Add production-side links in Green phase** - When making the test pass
3. **Update links in Refactor phase** - If you rename or restructure
4. **Validate frequently** - Catch broken links early

## What's Next?

- [Placeholder TDD](./placeholder-tdd) - Use placeholders for faster iteration
- [Complete Example](./complete-example) - A more comprehensive TDD walkthrough
