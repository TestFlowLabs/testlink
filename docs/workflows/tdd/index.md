# TDD with TestLink

This tutorial series teaches you how to integrate TestLink into your Test-Driven Development workflow. You'll learn when and how to add links during the TDD cycle.

## What is TDD?

Test-Driven Development is a software development approach where you:

1. **Write a failing test** (Red)
2. **Write minimal code to pass the test** (Green)
3. **Refactor the code** (Refactor)

This cycle repeats for each piece of functionality.

## How TestLink Enhances TDD

TestLink adds explicit traceability to TDD:

- **Document intent** - Links show which tests verify which production code
- **Prevent drift** - Validation catches broken links during refactoring
- **Enable navigation** - Jump between production code and tests in your IDE

## When to Add Links in TDD

The key question is: **when during the TDD cycle should you add links?**

| Phase | Add Links? | Why |
|-------|-----------|-----|
| Red (write test) | Yes - test side | Declare what you're testing |
| Green (make it pass) | Yes - production side | Document coverage |
| Refactor | Update if needed | Keep links accurate |

## Tutorials in This Series

| Tutorial | What You'll Learn |
|----------|-------------------|
| [Red-Green-Refactor](./red-green-refactor) | The classic TDD cycle with links at each phase |
| [Placeholders](./placeholders) | Using `@placeholder` markers for fast iteration |
| [Complete Example](./complete-example) | Build a `PriceCalculator` from scratch using TDD |

## Quick Start Example

Here's a preview of TDD with TestLink:

**Step 1: Write a failing test with link**

```php
test('calculates discount for premium users', function () {
    $calculator = new PriceCalculator();

    expect($calculator->calculateDiscount('premium', 100))->toBe(20);
})->linksAndCovers(PriceCalculator::class.'::calculateDiscount');
```

**Step 2: Write minimal production code with link**

```php
use TestFlowLabs\TestingAttributes\TestedBy;

class PriceCalculator
{
    #[TestedBy('Tests\PriceCalculatorTest', 'calculates discount for premium users')]
    public function calculateDiscount(string $userType, int $amount): int
    {
        if ($userType === 'premium') {
            return (int) ($amount * 0.2);
        }

        return 0;
    }
}
```

**Step 3: Validate**

```bash
./vendor/bin/testlink validate
✓ All links are valid!
```

::: tip Automate with Sync
Instead of manually adding links to both sides, you can add a link to one side and let `testlink sync` propagate it:

```bash
# Add #[TestedBy] to production, then run:
./vendor/bin/testlink sync

# Or add linksAndCovers() to test, then run:
./vendor/bin/testlink sync
```

See [How-to: Sync Links Automatically](/how-to/sync-links-automatically) for details.
:::

## Prerequisites

Before starting these tutorials:

- Complete [Getting Started](/tutorials/getting-started)
- Complete [Your First Bidirectional Link](/tutorials/first-bidirectional-link)
- Have a basic understanding of TDD concepts

Ready to begin? Start with [Red-Green-Refactor](./red-green-refactor)!
