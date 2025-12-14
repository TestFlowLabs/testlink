# BDD with TestLink

This tutorial series teaches you how to integrate TestLink into your Behavior-Driven Development workflow. You'll learn how to link both acceptance tests and unit tests to production code.

## What is BDD?

Behavior-Driven Development focuses on:

1. **Defining behavior** - Describe what the system should do
2. **Acceptance tests** - High-level tests that verify features
3. **Unit tests** - Low-level tests that verify implementation

BDD often uses a "double-loop" approach where acceptance tests drive unit tests.

## How TestLink Enhances BDD

TestLink adds traceability to BDD workflows:

- **Link acceptance tests** - Show which high-level behaviors are covered
- **Link unit tests** - Show which implementation details are verified
- **Use placeholders** - Work with behavior descriptions before implementation
- **Track coverage** - See the full picture from behavior to code

## Double-Loop BDD

```
┌──────────────────────────────────────────────────────────────────┐
│                         OUTER LOOP                                │
│  ┌─────────────────┐                    ┌─────────────────┐      │
│  │  Failing        │                    │  Passing        │      │
│  │  Acceptance     │                    │  Acceptance     │      │
│  │  Test           │                    │  Test           │      │
│  └────────┬────────┘                    └────────▲────────┘      │
│           │                                      │               │
│           ▼          INNER LOOP                  │               │
│  ┌────────────────────────────────────────────────┐              │
│  │  ┌─────────┐    ┌─────────┐    ┌───────────┐  │              │
│  │  │  RED    │ →  │  GREEN  │ →  │ REFACTOR  │──┼──────────────┘
│  │  │  Unit   │    │  Unit   │    │           │  │
│  │  │  Test   │    │  Test   │    │           │  │
│  │  └─────────┘    └─────────┘    └───────────┘  │
│  │                                               │
│  └───────────────────────────────────────────────┘
│
└──────────────────────────────────────────────────────────────────┘
```

## Tutorials in This Series

| Tutorial | What You'll Learn |
|----------|-------------------|
| [Double-Loop TDD](./double-loop) | The outer acceptance loop and inner unit loop |
| [Acceptance to Unit](./acceptance-to-unit) | Driving unit tests from acceptance tests |
| [Placeholder BDD](./placeholders) | Using placeholders in BDD workflows |
| [Complete Example](./complete-example) | Build a ShoppingCart feature using BDD |

## Quick Example

Here's a preview of BDD with TestLink:

**Acceptance Test (Outer Loop)**

```php
test('user can add item to cart and see updated total', function () {
    // Given a user with an empty cart
    $cart = new ShoppingCart();

    // When they add a $50 item
    $cart->addItem(new Product('Widget', 5000));

    // Then the cart shows the correct total
    expect($cart->getTotal())->toBe(5000);
})->linksAndCovers(ShoppingCart::class.'::addItem')
  ->linksAndCovers(ShoppingCart::class.'::getTotal');
```

**Unit Tests (Inner Loop)**

```php
describe('ShoppingCart', function () {
    describe('addItem', function () {
        test('adds item to items collection', function () {
            $cart = new ShoppingCart();
            $product = new Product('Widget', 5000);

            $cart->addItem($product);

            expect($cart->getItems())->toContain($product);
        })->linksAndCovers(ShoppingCart::class.'::addItem');
    });

    describe('getTotal', function () {
        test('sums all item prices', function () {
            $cart = new ShoppingCart();
            $cart->addItem(new Product('A', 1000));
            $cart->addItem(new Product('B', 2000));

            expect($cart->getTotal())->toBe(3000);
        })->linksAndCovers(ShoppingCart::class.'::getTotal');
    });
});
```

## Using `links()` vs `linksAndCovers()`

In BDD, you might have both acceptance and unit tests covering the same code:

- **`linksAndCovers()`** - Use for unit tests (includes in code coverage)
- **`links()`** - Use for acceptance/integration tests (traceability only)

This prevents double-counting in coverage reports.

```php
// Acceptance test - links only
test('complete checkout flow')
    ->links(CheckoutService::class.'::process');

// Unit test - links AND covers
test('validates payment details')
    ->linksAndCovers(CheckoutService::class.'::validatePayment');
```

## Prerequisites

Before starting these tutorials:

- Complete the [TDD tutorials](../tdd/)
- Understand basic BDD concepts
- Know the difference between acceptance and unit tests

Ready to begin? Start with [Double-Loop TDD](./double-loop)!
