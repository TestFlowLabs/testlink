# BDD with TestLink

Behavior-Driven Development (BDD) starts with business requirements expressed as scenarios and works down to implementation. TestLink adds traceability **after** the implementation exists, connecting scenarios to the code that fulfills them.

## The BDD Flow with TestLink

```
┌─────────────────────────────────────────────────────────┐
│  1. DISCOVER: Define behavior with stakeholders         │
│     ↓                                                   │
│  2. FORMULATE: Write scenarios (Gherkin)                │
│     ↓                                                   │
│  3. AUTOMATE: Create executable tests (NO links yet)    │
│     ↓                                                   │
│  4. IMPLEMENT: Build production code                    │
│     ↓                                                   │
│  5. LINK: Connect tests ↔ production with TestLink      │
│     ↓                                                   │
│  6. VALIDATE: Ensure complete traceability              │
└─────────────────────────────────────────────────────────┘
```

## Why Links Come After Implementation

In BDD, scenarios describe **what** the system should do, not **how**. When writing a scenario like "When I add a product to my cart", you don't yet know:

- Will it be a `CartService::addItem()` method?
- Will it be a `Cart::add()` method?
- Will it go through a `AddToCartAction` class?

**Links document the connection between behavior and implementation. You can't document what doesn't exist yet.**

## Using pest-plugin-bdd

TestLink works seamlessly with [pest-plugin-bdd](https://github.com/testflowlabs/pest-plugin-bdd) for Gherkin-style testing:

```bash
composer require --dev testflowlabs/pest-plugin-bdd
```

## Step-by-Step Example

### Step 1: DISCOVER & FORMULATE - Write Scenarios

Work with stakeholders to define behavior:

```gherkin
# features/shopping_cart.feature

Feature: Shopping Cart
  As a customer
  I want to add items to my cart
  So that I can purchase them later

  Scenario: Add item to empty cart
    Given I have an empty cart
    When I add a product priced at 29.99
    Then the cart total should be 29.99

  Scenario: Apply discount code
    Given I have a cart with items totaling 100.00
    When I apply discount code "SAVE20"
    Then the cart total should be 80.00
```

### Step 2: AUTOMATE - Create Executable Tests

Write the tests without links - focus on making scenarios executable:

```php
// tests/Feature/ShoppingCartTest.php

describe('Shopping Cart', function () {

    test('add item to empty cart', function () {
        // At this point, we don't know the implementation
        // Just make the scenario executable

        $cart = new Cart();  // or maybe CartService? We'll see...

        $cart->add(['price' => 29.99]);

        expect($cart->total())->toBe(29.99);
    });

    test('apply discount code', function () {
        $cart = new Cart();
        $cart->setTotal(100.00);

        // How will discounts work? We'll discover during implementation
        apply_discount($cart, 'SAVE20');

        expect($cart->total())->toBe(80.00);
    });

});
```

Tests fail - this is expected.

### Step 3: IMPLEMENT - Build Production Code

Now implement the code. The design emerges:

```php
// app/Services/CartService.php

namespace App\Services;

class CartService
{
    private array $items = [];
    private float $total = 0;

    public function add(array $item): void
    {
        $this->items[] = $item;
        $this->total += $item['price'];
    }

    public function total(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }
}
```

```php
// app/Services/DiscountService.php

namespace App\Services;

class DiscountService
{
    private array $codes = [
        'SAVE20' => 0.20,
        'SAVE10' => 0.10,
    ];

    public function apply(CartService $cart, string $code): void
    {
        if (isset($this->codes[$code])) {
            $discount = $cart->total() * $this->codes[$code];
            $cart->setTotal($cart->total() - $discount);
        }
    }
}
```

Update tests to use the real implementation:

```php
// tests/Feature/ShoppingCartTest.php

use App\Services\CartService;
use App\Services\DiscountService;

describe('Shopping Cart', function () {

    test('add item to empty cart', function () {
        $cart = new CartService();

        $cart->add(['price' => 29.99]);

        expect($cart->total())->toBe(29.99);
    });

    test('apply discount code', function () {
        $cart = new CartService();
        $cart->setTotal(100.00);

        $discountService = new DiscountService();
        $discountService->apply($cart, 'SAVE20');

        expect($cart->total())->toBe(80.00);
    });

});
```

Tests pass.

### Step 4: LINK - Add Traceability

**Now** that implementation exists, add bidirectional links:

```php
// tests/Feature/ShoppingCartTest.php

use App\Services\CartService;
use App\Services\DiscountService;

describe('Shopping Cart', function () {

    test('add item to empty cart', function () {
        $cart = new CartService();

        $cart->add(['price' => 29.99]);

        expect($cart->total())->toBe(29.99);
    })->linksAndCovers(CartService::class.'::add');  // Now we know what to link

    test('apply discount code', function () {
        $cart = new CartService();
        $cart->setTotal(100.00);

        $discountService = new DiscountService();
        $discountService->apply($cart, 'SAVE20');

        expect($cart->total())->toBe(80.00);
    })->linksAndCovers(DiscountService::class.'::apply');

});
```

```php
// app/Services/CartService.php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;

class CartService
{
    #[TestedBy('Tests\Feature\ShoppingCartTest', 'add item to empty cart')]
    public function add(array $item): void
    {
        $this->items[] = $item;
        $this->total += $item['price'];
    }

    // ...
}
```

```php
// app/Services/DiscountService.php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;

class DiscountService
{
    #[TestedBy('Tests\Feature\ShoppingCartTest', 'apply discount code')]
    public function apply(CartService $cart, string $code): void
    {
        // ...
    }
}
```

### Step 5: VALIDATE - Ensure Traceability

```bash
testlink validate
```

```
Validation Report:
  ✓ All links are synchronized!

  Bidirectional links: 2
```

## Using Placeholders in BDD

When developing multiple scenarios that map to the same service, placeholders speed up the workflow:

### During Development

```php
// tests/Feature/ShoppingCartTest.php

describe('Shopping Cart', function () {

    test('add item to empty cart', function () {
        $cart = new CartService();
        $cart->add(['price' => 29.99]);
        expect($cart->total())->toBe(29.99);
    })->linksAndCovers('@cart');  // Placeholder

    test('add multiple items', function () {
        $cart = new CartService();
        $cart->add(['price' => 10.00]);
        $cart->add(['price' => 20.00]);
        expect($cart->total())->toBe(30.00);
    })->linksAndCovers('@cart');  // Same placeholder

    test('apply discount code', function () {
        // ...
    })->linksAndCovers('@discount');  // Different feature

});
```

```php
// app/Services/CartService.php

class CartService
{
    #[TestedBy('@cart')]  // Links to all tests with @cart
    public function add(array $item): void
    {
        // ...
    }
}
```

### Resolve Before Committing

```bash
testlink pair --dry-run  # Preview
testlink pair            # Apply
```

The `@cart` placeholder links the `add()` method to both "add item to empty cart" and "add multiple items" tests automatically (N:M matching).

See the [Placeholder Pairing Guide](/guide/placeholder-pairing) for complete documentation.

## With pest-plugin-bdd Step Definitions

If using Gherkin step definitions:

```php
// tests/Steps/CartSteps.php

use App\Services\CartService;
use App\Services\DiscountService;
use TestFlowLabs\TestingAttributes\Given;
use TestFlowLabs\TestingAttributes\When;
use TestFlowLabs\TestingAttributes\Then;

#[Given('I have an empty cart')]
function givenEmptyCart(): CartService
{
    return new CartService();
}

#[When('I add a product priced at :price')]
function whenAddProduct(CartService $cart, float $price): CartService
{
    $cart->add(['price' => $price]);
    return $cart;
}

#[When('I apply discount code :code')]
function whenApplyDiscount(CartService $cart, string $code): CartService
{
    $discountService = new DiscountService();
    $discountService->apply($cart, $code);
    return $cart;
}

#[Then('the cart total should be :expected')]
function thenTotalShouldBe(CartService $cart, float $expected): void
{
    expect($cart->total())->toBe($expected);
}
```

Links are still added after implementation, in the scenario test files.

## Traceability Layers

BDD often creates multiple test layers. Use different link types for each:

| Test Layer | Link Type | Why |
|------------|-----------|-----|
| **Acceptance/E2E** | `links()` | Traces behavior, coverage tracked at lower levels |
| **Integration** | `linksAndCovers()` | Both traceability and coverage |
| **Unit** | `linksAndCovers()` | Primary coverage tracking |

### Example: Multi-Layer Linking

```php
// Acceptance test - behavior traceability only
test('user can complete checkout', function () {
    // High-level E2E test
})->links(CheckoutController::class.'::process');

// Integration test - traces and covers service
test('checkout service processes order', function () {
    // Service integration test
})->linksAndCovers(CheckoutService::class.'::process');

// Unit test - covers specific calculation
test('calculates order total with tax', function () {
    // Focused unit test
})->linksAndCovers(OrderCalculator::class.'::calculateTotal');
```

Use `links()` at higher levels to avoid counting coverage twice while maintaining full traceability.

## Coverage Report

```bash
testlink report
```

```
Coverage Links Report
─────────────────────

App\Services\CartService
  add()
    → Tests\Feature\ShoppingCartTest::add item to empty cart (linksAndCovers)
    → Tests\Unit\CartServiceTest::test_adds_item (linksAndCovers)

App\Services\DiscountService
  apply()
    → Tests\Feature\ShoppingCartTest::apply discount code (linksAndCovers)
    → Tests\E2E\CheckoutTest::completes purchase with discount (links)

Summary:
  Methods: 2
  Test links: 4
```

## Summary

TestLink in BDD:

1. **Scenarios first** - Write behavior descriptions without implementation details
2. **Implement to satisfy scenarios** - Let the design emerge
3. **Link after implementation** - Document what exists, not what might exist
4. **Multi-layer traceability** - Use `links()` vs `linksAndCovers()` appropriately
5. **Validate continuously** - Ensure scenarios stay connected to implementation

The value of TestLink in BDD is answering: "Which code implements this scenario?" and "Which scenarios verify this code?" - questions that can only be answered after implementation exists.
