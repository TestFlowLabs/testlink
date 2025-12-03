# BDD with TestLink

Behavior-Driven Development (BDD) starts with business requirements and works down to implementation. TestLink helps trace the connection between high-level scenarios and the production code that implements them.

## The BDD Flow with TestLink

```
┌─────────────────────────────────────────────────────────┐
│  1. FEATURE: Define business behavior (Gherkin)         │
│     ↓                                                   │
│  2. SCENARIO: Write acceptance tests                    │
│     ↓                                                   │
│  3. IMPLEMENT: Build production code with #[TestedBy]   │
│     ↓                                                   │
│  4. LINK: Connect tests to production with linksAndCovers│
│     ↓                                                   │
│  5. VALIDATE: Ensure complete traceability              │
└─────────────────────────────────────────────────────────┘
```

## Using pest-plugin-bdd

TestLink works seamlessly with [pest-plugin-bdd](https://github.com/testflowlabs/pest-plugin-bdd) for Gherkin-style testing:

```bash
composer require --dev testflowlabs/pest-plugin-bdd
```

### Feature File

```gherkin
# features/shopping_cart.feature

Feature: Shopping Cart
  As a customer
  I want to add items to my cart
  So that I can purchase them later

  Scenario: Add item to empty cart
    Given I have an empty cart
    When I add a product with price 29.99
    Then the cart total should be 29.99

  Scenario: Apply discount code
    Given I have a cart with total 100.00
    When I apply discount code "SAVE20"
    Then the cart total should be 80.00
```

### Step Definitions with Links

Using `pest-plugin-bdd`, define steps with the `#[Given]`, `#[When]`, `#[Then]` attributes from `test-attributes`:

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

#[Given('I have a cart with total :total')]
function givenCartWithTotal(float $total): CartService
{
    $cart = new CartService();
    $cart->setTotal($total);
    return $cart;
}

#[When('I add a product with price :price')]
function whenAddProduct(CartService $cart, float $price): CartService
{
    $cart->addItem(['price' => $price]);
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
function thenCartTotalShouldBe(CartService $cart, float $expected): void
{
    expect($cart->getTotal())->toBe($expected);
}
```

### Scenario Tests with Coverage Links

```php
// tests/Feature/ShoppingCartTest.php

use App\Services\CartService;
use App\Services\DiscountService;

describe('Shopping Cart', function () {

    test('add item to empty cart', function () {
        given('I have an empty cart');
        when('I add a product with price 29.99');
        then('the cart total should be 29.99');
    })->linksAndCovers(CartService::class.'::addItem');

    test('apply discount code', function () {
        given('I have a cart with total 100.00');
        when('I apply discount code "SAVE20"');
        then('the cart total should be 80.00');
    })->linksAndCovers(DiscountService::class.'::apply');

});
```

### Production Code with TestedBy

```php
// app/Services/CartService.php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;

class CartService
{
    private float $total = 0;
    private array $items = [];

    #[TestedBy('Tests\Feature\ShoppingCartTest', 'add item to empty cart')]
    public function addItem(array $item): void
    {
        $this->items[] = $item;
        $this->total += $item['price'];
    }

    public function getTotal(): float
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

use TestFlowLabs\TestingAttributes\TestedBy;

class DiscountService
{
    private array $discounts = [
        'SAVE20' => 0.20,
        'SAVE10' => 0.10,
    ];

    #[TestedBy('Tests\Feature\ShoppingCartTest', 'apply discount code')]
    public function apply(CartService $cart, string $code): void
    {
        if (isset($this->discounts[$code])) {
            $discount = $cart->getTotal() * $this->discounts[$code];
            $cart->setTotal($cart->getTotal() - $discount);
        }
    }
}
```

## Traceability Layers

BDD creates multiple layers of tests. TestLink helps track coverage at each layer:

```
┌─────────────────────────────────────────────┐
│  Feature Tests (Acceptance)                 │
│  └─ links() to integration points           │
├─────────────────────────────────────────────┤
│  Integration Tests                          │
│  └─ linksAndCovers() to service methods     │
├─────────────────────────────────────────────┤
│  Unit Tests                                 │
│  └─ linksAndCovers() to specific methods    │
└─────────────────────────────────────────────┘
```

### Example: Multi-Layer Linking

```php
// Feature test - traces behavior, no coverage
test('complete checkout flow', function () {
    // High-level acceptance test
})->links(CheckoutController::class.'::process');

// Integration test - traces and covers
test('checkout service processes order', function () {
    // Service-level test
})->linksAndCovers(CheckoutService::class.'::process');

// Unit test - traces and covers specific method
test('calculates order total', function () {
    // Method-level test
})->linksAndCovers(OrderCalculator::class.'::calculate');
```

## Links vs LinksAndCovers in BDD

| Test Type | Method | Purpose |
|-----------|--------|---------|
| **Acceptance/Feature** | `links()` | Traceability only, coverage tracked elsewhere |
| **Integration** | `linksAndCovers()` | Both traceability and coverage |
| **Unit** | `linksAndCovers()` | Primary coverage tracking |

Use `links()` for high-level tests to avoid duplicate coverage while maintaining traceability.

## Validation Report

```bash
testlink report
```

```
Coverage Links Report
─────────────────────

App\Services\CartService
  addItem()
    → Tests\Feature\ShoppingCartTest::add item to empty cart
    → Tests\Unit\CartServiceTest::test_adds_item_to_cart

App\Services\DiscountService
  apply()
    → Tests\Feature\ShoppingCartTest::apply discount code
    → Tests\Unit\DiscountServiceTest::test_applies_percentage_discount

Summary:
  Methods: 2
  Tests: 4
```

## Benefits in BDD

1. **Business traceability**: Link scenarios directly to implementation
2. **Coverage at right level**: Use `links()` for acceptance, `linksAndCovers()` for unit
3. **Living documentation**: `#[TestedBy]` shows which scenarios test each method
4. **Sync validation**: Ensure all scenarios have corresponding implementations
5. **Ecosystem integration**: Works with `pest-plugin-bdd` and `test-attributes`
