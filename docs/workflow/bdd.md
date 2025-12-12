# BDD with TestLink

Behavior-Driven Development (BDD) starts with business requirements expressed as scenarios and works toward implementation. TestLink adds traceability **after** implementation, documenting the connection between behavior and code.

## The BDD Cycle

BDD uses a "double loop" approach:

```
┌─────────────────────────────────────────────────────────────────────┐
│                      OUTER LOOP (Acceptance)                        │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │                                                               │  │
│  │   Acceptance    ┌─────────────────────────────────────┐       │  │
│  │   Test (RED) ──►│        INNER LOOP (Unit TDD)        │       │  │
│  │                 │                                     │       │  │
│  │                 │    ┌─────┐     ┌───────┐            │       │  │
│  │                 │    │ RED │────►│ GREEN │            │       │  │
│  │                 │    └─────┘     └───┬───┘            │       │  │
│  │                 │        ▲           │                │       │  │
│  │                 │        │      ┌────▼────┐           │       │  │
│  │                 │        └──────│REFACTOR │           │       │  │
│  │                 │               └─────────┘           │       │  │
│  │                 │                                     │       │  │
│  │                 │    (repeat for each unit needed)    │       │  │
│  │                 └──────────────────┬──────────────────┘       │  │
│  │                                    │                          │  │
│  │                                    ▼                          │  │
│  │                           Acceptance Test (GREEN)             │  │
│  │                                                               │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│                      (repeat for each scenario)                     │
└─────────────────────────────────────────────────────────────────────┘
```

**Outer Loop (Acceptance):**
1. Write an acceptance test for a scenario - it fails (RED)
2. Run inner TDD loops until all units are implemented
3. Acceptance test passes (GREEN)

**Inner Loop (Unit TDD):**
1. Write a unit test - it fails (RED)
2. Write minimum code to pass (GREEN)
3. Refactor while keeping tests green

## Where Does TestLink Fit?

TestLink comes **after** the implementation phase, when the design is stable:

```
┌──────────────────────────────────────────────────────────────────┐
│                    BDD Flow + TestLink                           │
│                                                                  │
│  DISCOVER    FORMULATE    AUTOMATE    IMPLEMENT    + TESTLINK    │
│  ─────────   ─────────    ────────    ─────────    ───────────   │
│                                                                  │
│  Talk to     Write        Write       Build code   Add links     │
│  stakeholders scenarios   tests       (TDD loops)  Validate      │
│              (Gherkin)    (failing)                              │
│                                                                  │
│  ◄─────────────── BDD ───────────────►◄── TestLink ──►           │
└──────────────────────────────────────────────────────────────────┘
```

## Why Links Come After Implementation

In BDD, scenarios describe **what** the system should do, not **how**. When writing a scenario like "When I add a product to my cart", you don't yet know:

- Will it be a `CartService::addItem()` method?
- Will it be a `ShoppingCart::add()` method?
- Will it go through an `AddToCartAction` class?

**Links document the connection between behavior and implementation. You can't document what doesn't exist yet.**

## Step-by-Step Example: E-Commerce Shopping Cart

Let's build a shopping cart feature using BDD with TestLink.

### Phase 1: Discover & Formulate

Work with stakeholders to define behavior in Gherkin:

```gherkin
# features/shopping_cart.feature

Feature: Shopping Cart
  As a customer
  I want to manage items in my shopping cart
  So that I can purchase products later

  Scenario: Add item to empty cart
    Given I have an empty cart
    When I add a product priced at 29.99
    Then the cart total should be 29.99

  Scenario: Add multiple items to cart
    Given I have an empty cart
    When I add a product priced at 10.00
    And I add a product priced at 20.00
    Then the cart total should be 30.00

  Scenario: Apply percentage discount
    Given I have a cart with total 100.00
    When I apply discount code "SAVE20"
    Then the cart total should be 80.00

  Scenario: Apply invalid discount code
    Given I have a cart with total 100.00
    When I apply discount code "INVALID"
    Then the cart total should remain 100.00
    And I should see error "Invalid discount code"
```

### Phase 2: Automate - Write Acceptance Test (OUTER LOOP - RED)

Write the acceptance test without knowing the implementation:

::: code-group

```php [Pest]
// tests/Feature/ShoppingCartTest.php

describe('Shopping Cart', function () {

    test('add item to empty cart', function () {
        // We don't know the implementation yet
        // Just make the scenario executable
        $cart = new CartService();

        $cart->addItem(['price' => 29.99]);

        expect($cart->total())->toBe(29.99);
    });

    test('add multiple items to cart', function () {
        $cart = new CartService();

        $cart->addItem(['price' => 10.00]);
        $cart->addItem(['price' => 20.00]);

        expect($cart->total())->toBe(30.00);
    });

    test('apply percentage discount', function () {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $discountService->apply($cart, 'SAVE20');

        expect($cart->total())->toBe(80.00);
    });

    test('apply invalid discount code', function () {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $result = $discountService->apply($cart, 'INVALID');

        expect($cart->total())->toBe(100.00);
        expect($result->error)->toBe('Invalid discount code');
    });

});
```

```php [PHPUnit]
// tests/Feature/ShoppingCartTest.php

namespace Tests\Feature;

use App\Services\CartService;
use App\Services\DiscountService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ShoppingCartTest extends TestCase
{
    #[Test]
    public function add_item_to_empty_cart(): void
    {
        // We don't know the implementation yet
        // Just make the scenario executable
        $cart = new CartService();

        $cart->addItem(['price' => 29.99]);

        $this->assertSame(29.99, $cart->total());
    }

    #[Test]
    public function add_multiple_items_to_cart(): void
    {
        $cart = new CartService();

        $cart->addItem(['price' => 10.00]);
        $cart->addItem(['price' => 20.00]);

        $this->assertSame(30.00, $cart->total());
    }

    #[Test]
    public function apply_percentage_discount(): void
    {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $discountService->apply($cart, 'SAVE20');

        $this->assertSame(80.00, $cart->total());
    }

    #[Test]
    public function apply_invalid_discount_code(): void
    {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $result = $discountService->apply($cart, 'INVALID');

        $this->assertSame(100.00, $cart->total());
        $this->assertSame('Invalid discount code', $result->error);
    }
}
```

:::

Run tests - they fail because classes don't exist:

::: code-group

```bash [Pest]
pest tests/Feature/ShoppingCartTest.php
```

```bash [PHPUnit]
phpunit tests/Feature/ShoppingCartTest.php
```

:::

```
   FAIL  Tests\Feature\ShoppingCartTest
  ✗ add item to empty cart
    Error: Class "CartService" not found

  Tests:    4 failed
  Duration: 0.05s
```

**This is expected!** The acceptance test is RED. Now we enter the inner TDD loop.

---

### Phase 3: Inner TDD Loop

The acceptance test needs `CartService` and `DiscountService`. We'll build each with TDD.

---

#### Inner Loop Cycle 1: CartService::addItem()

**RED - Write failing unit test:**

::: code-group

```php [Pest]
// tests/Unit/CartServiceTest.php

use App\Services\CartService;

describe('CartService', function () {

    test('add item increases total', function () {
        $cart = new CartService();

        $cart->addItem(['price' => 29.99]);

        expect($cart->total())->toBe(29.99);
    });

});
```

```php [PHPUnit]
// tests/Unit/CartServiceTest.php

namespace Tests\Unit;

use App\Services\CartService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CartServiceTest extends TestCase
{
    #[Test]
    public function add_item_increases_total(): void
    {
        $cart = new CartService();

        $cart->addItem(['price' => 29.99]);

        $this->assertSame(29.99, $cart->total());
    }
}
```

:::

Run the unit test:

::: code-group

```bash [Pest]
pest tests/Unit/CartServiceTest.php
```

```bash [PHPUnit]
phpunit tests/Unit/CartServiceTest.php
```

:::

```
   FAIL  Tests\Unit\CartServiceTest
  ✗ add item increases total
    Error: Class "App\Services\CartService" not found

  Tests:    1 failed
  Duration: 0.02s
```

**GREEN - Write minimum code:**

```php
// app/Services/CartService.php

namespace App\Services;

class CartService
{
    private float $total = 0;

    public function addItem(array $item): void
    {
        $this->total += $item['price'];
    }

    public function total(): float
    {
        return $this->total;
    }
}
```

Run the unit test:

```
   PASS  Tests\Unit\CartServiceTest
  ✓ add item increases total

  Tests:    1 passed
  Duration: 0.02s
```

**REFACTOR - No refactoring needed yet.**

---

#### Inner Loop Cycle 2: CartService::setTotal()

The discount scenario needs `setTotal()`. Let's add it.

**RED:**

::: code-group

```php [Pest]
// tests/Unit/CartServiceTest.php (add to describe block)

test('set total overrides current total', function () {
    $cart = new CartService();
    $cart->addItem(['price' => 50.00]);

    $cart->setTotal(100.00);

    expect($cart->total())->toBe(100.00);
});
```

```php [PHPUnit]
// tests/Unit/CartServiceTest.php (add method)

#[Test]
public function set_total_overrides_current_total(): void
{
    $cart = new CartService();
    $cart->addItem(['price' => 50.00]);

    $cart->setTotal(100.00);

    $this->assertSame(100.00, $cart->total());
}
```

:::

```
   FAIL  Tests\Unit\CartServiceTest
  ✓ add item increases total
  ✗ set total overrides current total
    Error: Call to undefined method App\Services\CartService::setTotal()

  Tests:    1 passed, 1 failed
  Duration: 0.02s
```

**GREEN:**

```php
// app/Services/CartService.php

public function setTotal(float $total): void
{
    $this->total = $total;
}
```

```
   PASS  Tests\Unit\CartServiceTest
  ✓ add item increases total
  ✓ set total overrides current total

  Tests:    2 passed
  Duration: 0.02s
```

---

#### Inner Loop Cycle 3: DiscountService::apply()

Now we need `DiscountService` for the discount scenarios.

**RED:**

::: code-group

```php [Pest]
// tests/Unit/DiscountServiceTest.php

use App\Services\CartService;
use App\Services\DiscountService;

describe('DiscountService', function () {

    test('apply valid discount reduces total', function () {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $discountService->apply($cart, 'SAVE20');

        expect($cart->total())->toBe(80.00);
    });

});
```

```php [PHPUnit]
// tests/Unit/DiscountServiceTest.php

namespace Tests\Unit;

use App\Services\CartService;
use App\Services\DiscountService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DiscountServiceTest extends TestCase
{
    #[Test]
    public function apply_valid_discount_reduces_total(): void
    {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $discountService->apply($cart, 'SAVE20');

        $this->assertSame(80.00, $cart->total());
    }
}
```

:::

```
   FAIL  Tests\Unit\DiscountServiceTest
  ✗ apply valid discount reduces total
    Error: Class "App\Services\DiscountService" not found

  Tests:    1 failed
  Duration: 0.02s
```

**GREEN:**

```php
// app/Services/DiscountService.php

namespace App\Services;

class DiscountService
{
    private array $codes = [
        'SAVE20' => 0.20,
        'SAVE10' => 0.10,
    ];

    public function apply(CartService $cart, string $code): DiscountResult
    {
        if (!isset($this->codes[$code])) {
            return new DiscountResult(error: 'Invalid discount code');
        }

        $discount = $cart->total() * $this->codes[$code];
        $cart->setTotal($cart->total() - $discount);

        return new DiscountResult();
    }
}
```

```php
// app/Services/DiscountResult.php

namespace App\Services;

readonly class DiscountResult
{
    public function __construct(
        public ?string $error = null
    ) {}
}
```

```
   PASS  Tests\Unit\DiscountServiceTest
  ✓ apply valid discount reduces total

  Tests:    1 passed
  Duration: 0.02s
```

---

#### Inner Loop Cycle 4: Invalid Discount Code

**RED:**

::: code-group

```php [Pest]
// tests/Unit/DiscountServiceTest.php (add to describe block)

test('apply invalid discount returns error', function () {
    $cart = new CartService();
    $cart->setTotal(100.00);
    $discountService = new DiscountService();

    $result = $discountService->apply($cart, 'INVALID');

    expect($cart->total())->toBe(100.00);
    expect($result->error)->toBe('Invalid discount code');
});
```

```php [PHPUnit]
// tests/Unit/DiscountServiceTest.php (add method)

#[Test]
public function apply_invalid_discount_returns_error(): void
{
    $cart = new CartService();
    $cart->setTotal(100.00);
    $discountService = new DiscountService();

    $result = $discountService->apply($cart, 'INVALID');

    $this->assertSame(100.00, $cart->total());
    $this->assertSame('Invalid discount code', $result->error);
}
```

:::

```
   PASS  Tests\Unit\DiscountServiceTest
  ✓ apply valid discount reduces total
  ✓ apply invalid discount returns error

  Tests:    2 passed
  Duration: 0.02s
```

The test passes immediately because we already implemented error handling.

---

### Phase 4: Acceptance Tests Pass (OUTER LOOP - GREEN)

Now run the acceptance tests:

::: code-group

```bash [Pest]
pest tests/Feature/ShoppingCartTest.php
```

```bash [PHPUnit]
phpunit tests/Feature/ShoppingCartTest.php
```

:::

```
   PASS  Tests\Feature\ShoppingCartTest
  ✓ add item to empty cart
  ✓ add multiple items to cart
  ✓ apply percentage discount
  ✓ apply invalid discount code

  Tests:    4 passed
  Duration: 0.05s
```

All acceptance tests are GREEN. The outer loop is complete.

Run all tests to verify:

::: code-group

```bash [Pest]
pest
```

```bash [PHPUnit]
phpunit
```

:::

```
   PASS  Tests\Unit\CartServiceTest
  ✓ add item increases total
  ✓ set total overrides current total

   PASS  Tests\Unit\DiscountServiceTest
  ✓ apply valid discount reduces total
  ✓ apply invalid discount returns error

   PASS  Tests\Feature\ShoppingCartTest
  ✓ add item to empty cart
  ✓ add multiple items to cart
  ✓ apply percentage discount
  ✓ apply invalid discount code

  Tests:    8 passed
  Duration: 0.08s
```

---

### Phase 5: Design Stabilizes - Add TestLink

Now that the design is stable, add bidirectional links for traceability.

**Add links to unit tests:**

::: code-group

```php [Pest]
// tests/Unit/CartServiceTest.php

use App\Services\CartService;

describe('CartService', function () {

    test('add item increases total', function () {
        $cart = new CartService();

        $cart->addItem(['price' => 29.99]);

        expect($cart->total())->toBe(29.99);
    })->linksAndCovers(CartService::class.'::addItem');

    test('set total overrides current total', function () {
        $cart = new CartService();
        $cart->addItem(['price' => 50.00]);

        $cart->setTotal(100.00);

        expect($cart->total())->toBe(100.00);
    })->linksAndCovers(CartService::class.'::setTotal');

});
```

```php [PHPUnit]
// tests/Unit/CartServiceTest.php

namespace Tests\Unit;

use App\Services\CartService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class CartServiceTest extends TestCase
{
    #[Test]
    #[LinksAndCovers(CartService::class, 'addItem')]
    public function add_item_increases_total(): void
    {
        $cart = new CartService();

        $cart->addItem(['price' => 29.99]);

        $this->assertSame(29.99, $cart->total());
    }

    #[Test]
    #[LinksAndCovers(CartService::class, 'setTotal')]
    public function set_total_overrides_current_total(): void
    {
        $cart = new CartService();
        $cart->addItem(['price' => 50.00]);

        $cart->setTotal(100.00);

        $this->assertSame(100.00, $cart->total());
    }
}
```

:::

::: code-group

```php [Pest]
// tests/Unit/DiscountServiceTest.php

use App\Services\CartService;
use App\Services\DiscountService;

describe('DiscountService', function () {

    test('apply valid discount reduces total', function () {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $discountService->apply($cart, 'SAVE20');

        expect($cart->total())->toBe(80.00);
    })->linksAndCovers(DiscountService::class.'::apply');

    test('apply invalid discount returns error', function () {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $result = $discountService->apply($cart, 'INVALID');

        expect($cart->total())->toBe(100.00);
        expect($result->error)->toBe('Invalid discount code');
    })->linksAndCovers(DiscountService::class.'::apply');

});
```

```php [PHPUnit]
// tests/Unit/DiscountServiceTest.php

namespace Tests\Unit;

use App\Services\CartService;
use App\Services\DiscountService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class DiscountServiceTest extends TestCase
{
    #[Test]
    #[LinksAndCovers(DiscountService::class, 'apply')]
    public function apply_valid_discount_reduces_total(): void
    {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $discountService->apply($cart, 'SAVE20');

        $this->assertSame(80.00, $cart->total());
    }

    #[Test]
    #[LinksAndCovers(DiscountService::class, 'apply')]
    public function apply_invalid_discount_returns_error(): void
    {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();

        $result = $discountService->apply($cart, 'INVALID');

        $this->assertSame(100.00, $cart->total());
        $this->assertSame('Invalid discount code', $result->error);
    }
}
```

:::

**Add links to acceptance tests (using `links()` for traceability only):**

::: code-group

```php [Pest]
// tests/Feature/ShoppingCartTest.php

use App\Services\CartService;
use App\Services\DiscountService;

describe('Shopping Cart', function () {

    test('add item to empty cart', function () {
        $cart = new CartService();
        $cart->addItem(['price' => 29.99]);
        expect($cart->total())->toBe(29.99);
    })->links(CartService::class.'::addItem');

    test('add multiple items to cart', function () {
        $cart = new CartService();
        $cart->addItem(['price' => 10.00]);
        $cart->addItem(['price' => 20.00]);
        expect($cart->total())->toBe(30.00);
    })->links(CartService::class.'::addItem');

    test('apply percentage discount', function () {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();
        $discountService->apply($cart, 'SAVE20');
        expect($cart->total())->toBe(80.00);
    })->links(DiscountService::class.'::apply');

    test('apply invalid discount code', function () {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();
        $result = $discountService->apply($cart, 'INVALID');
        expect($cart->total())->toBe(100.00);
        expect($result->error)->toBe('Invalid discount code');
    })->links(DiscountService::class.'::apply');

});
```

```php [PHPUnit]
// tests/Feature/ShoppingCartTest.php

namespace Tests\Feature;

use App\Services\CartService;
use App\Services\DiscountService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\TestingAttributes\Links;

class ShoppingCartTest extends TestCase
{
    #[Test]
    #[Links(CartService::class, 'addItem')]
    public function add_item_to_empty_cart(): void
    {
        $cart = new CartService();
        $cart->addItem(['price' => 29.99]);
        $this->assertSame(29.99, $cart->total());
    }

    #[Test]
    #[Links(CartService::class, 'addItem')]
    public function add_multiple_items_to_cart(): void
    {
        $cart = new CartService();
        $cart->addItem(['price' => 10.00]);
        $cart->addItem(['price' => 20.00]);
        $this->assertSame(30.00, $cart->total());
    }

    #[Test]
    #[Links(DiscountService::class, 'apply')]
    public function apply_percentage_discount(): void
    {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();
        $discountService->apply($cart, 'SAVE20');
        $this->assertSame(80.00, $cart->total());
    }

    #[Test]
    #[Links(DiscountService::class, 'apply')]
    public function apply_invalid_discount_code(): void
    {
        $cart = new CartService();
        $cart->setTotal(100.00);
        $discountService = new DiscountService();
        $result = $discountService->apply($cart, 'INVALID');
        $this->assertSame(100.00, $cart->total());
        $this->assertSame('Invalid discount code', $result->error);
    }
}
```

:::

**Add TestedBy to production code:**

```php
// app/Services/CartService.php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;
use Tests\Unit\CartServiceTest;
use Tests\Feature\ShoppingCartTest;

class CartService
{
    private float $total = 0;

    #[TestedBy(CartServiceTest::class, 'add item increases total')]
    #[TestedBy(ShoppingCartTest::class, 'add item to empty cart')]
    #[TestedBy(ShoppingCartTest::class, 'add multiple items to cart')]
    public function addItem(array $item): void
    {
        $this->total += $item['price'];
    }

    public function total(): float
    {
        return $this->total;
    }

    #[TestedBy(CartServiceTest::class, 'set total overrides current total')]
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
use Tests\Unit\DiscountServiceTest;
use Tests\Feature\ShoppingCartTest;

class DiscountService
{
    private array $codes = [
        'SAVE20' => 0.20,
        'SAVE10' => 0.10,
    ];

    #[TestedBy(DiscountServiceTest::class, 'apply valid discount reduces total')]
    #[TestedBy(DiscountServiceTest::class, 'apply invalid discount returns error')]
    #[TestedBy(ShoppingCartTest::class, 'apply percentage discount')]
    #[TestedBy(ShoppingCartTest::class, 'apply invalid discount code')]
    public function apply(CartService $cart, string $code): DiscountResult
    {
        if (!isset($this->codes[$code])) {
            return new DiscountResult(error: 'Invalid discount code');
        }

        $discount = $cart->total() * $this->codes[$code];
        $cart->setTotal($cart->total() - $discount);

        return new DiscountResult();
    }
}
```

**Validate the links:**

```bash
testlink validate
```

```
  Validation Report
  -----------------

  Link Summary
    PHPUnit attribute links: 8
    Pest method chain links: 0
    Total links: 8

  ✓ All links are valid!
```

**View the coverage report:**

```bash
testlink report
```

```
  Coverage Links Report
  ---------------------

  App\Services\CartService
    addItem()
      → CartServiceTest::add item increases total (linksAndCovers)
      → ShoppingCartTest::add item to empty cart (links)
      → ShoppingCartTest::add multiple items to cart (links)
    setTotal()
      → CartServiceTest::set total overrides current total (linksAndCovers)

  App\Services\DiscountService
    apply()
      → DiscountServiceTest::apply valid discount reduces total (linksAndCovers)
      → DiscountServiceTest::apply invalid discount returns error (linksAndCovers)
      → ShoppingCartTest::apply percentage discount (links)
      → ShoppingCartTest::apply invalid discount code (links)

  Summary
    Methods with tests: 3
    Total test links: 8
```

---

## Using Placeholders for Faster Iteration

During rapid BDD development, use placeholders to avoid premature linking:

### During Development (Placeholders)

::: code-group

```php [Pest Tests]
// tests/Feature/ShoppingCartTest.php

describe('Shopping Cart', function () {

    test('add item to empty cart', function () {
        $cart = new CartService();
        $cart->addItem(['price' => 29.99]);
        expect($cart->total())->toBe(29.99);
    })->linksAndCovers('@cart-add');

    test('add multiple items', function () {
        $cart = new CartService();
        $cart->addItem(['price' => 10.00]);
        $cart->addItem(['price' => 20.00]);
        expect($cart->total())->toBe(30.00);
    })->linksAndCovers('@cart-add');

    test('apply discount', function () {
        // ...
    })->linksAndCovers('@discount');

});
```

```php [PHPUnit Tests]
// tests/Feature/ShoppingCartTest.php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class ShoppingCartTest extends TestCase
{
    #[Test]
    #[LinksAndCovers('@cart-add')]
    public function add_item_to_empty_cart(): void
    {
        $cart = new CartService();
        $cart->addItem(['price' => 29.99]);
        $this->assertSame(29.99, $cart->total());
    }

    #[Test]
    #[LinksAndCovers('@cart-add')]
    public function add_multiple_items(): void
    {
        $cart = new CartService();
        $cart->addItem(['price' => 10.00]);
        $cart->addItem(['price' => 20.00]);
        $this->assertSame(30.00, $cart->total());
    }

    #[Test]
    #[LinksAndCovers('@discount')]
    public function apply_discount(): void
    {
        // ...
    }
}
```

:::

```php
// app/Services/CartService.php

#[TestedBy('@cart-add')]
public function addItem(array $item): void
{
    // ...
}
```

```php
// app/Services/DiscountService.php

#[TestedBy('@discount')]
public function apply(CartService $cart, string $code): DiscountResult
{
    // ...
}
```

### Check Placeholder Status

```bash
testlink validate
```

```
  Validation Report
  -----------------

  Unresolved Placeholders
    ⚠ @cart-add  (1 production, 2 tests)
    ⚠ @discount  (1 production, 1 tests)

  Run "testlink pair" to resolve placeholders.

  Link Summary
    PHPUnit attribute links: 0
    Pest method chain links: 0
    Total links: 0
```

### Resolve When Design Stabilizes

```bash
testlink pair --dry-run
```

```
  Pairing Placeholders
  --------------------

  Running in dry-run mode. No files will be modified.

  Scanning for placeholders...

  Found Placeholders
    ✓ @cart-add   1 production × 2 tests = 2 links
    ✓ @discount   1 production × 1 tests = 1 links

  Production Files
    app/Services/CartService.php
      @cart-add → ShoppingCartTest::add item to empty cart
      @cart-add → ShoppingCartTest::add multiple items

    app/Services/DiscountService.php
      @discount → ShoppingCartTest::apply discount

  Test Files
    tests/Feature/ShoppingCartTest.php
      @cart-add → CartService::addItem
      @discount → DiscountService::apply

  Dry run complete. Would modify 3 file(s) with 5 change(s).
```

```bash
testlink pair
```

```
  Pairing Placeholders
  --------------------

  Scanning for placeholders...

  Found Placeholders
    ✓ @cart-add   1 production × 2 tests = 2 links
    ✓ @discount   1 production × 1 tests = 1 links

  Production Files
    app/Services/CartService.php
      @cart-add → ShoppingCartTest::add item to empty cart
      @cart-add → ShoppingCartTest::add multiple items

    app/Services/DiscountService.php
      @discount → ShoppingCartTest::apply discount

  Test Files
    tests/Feature/ShoppingCartTest.php
      @cart-add → CartService::addItem
      @discount → DiscountService::apply

  Pairing complete. Modified 3 file(s) with 5 change(s).
```

See the [Placeholder Pairing Guide](/guide/placeholder-pairing) for complete documentation.

---

## Integration with pest-plugin-bdd

[pest-plugin-bdd](https://github.com/testflowlabs/pest-plugin-bdd) provides Gherkin-style step definitions for Pest. TestLink complements it with traceability.

### Installing pest-plugin-bdd

```bash
composer require --dev testflowlabs/pest-plugin-bdd
```

::: info Two-Package Architecture
The `#[Given]`, `#[When]`, `#[Then]` attributes are in the `testflowlabs/test-attributes` package (production dependency). This is because step definition functions may be referenced from production code and attributes must be available at runtime.
:::

### Writing Step Definitions

Step definitions use attributes from `test-attributes`:

```php
// tests/Steps/CartSteps.php

namespace Tests\Steps;

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

#[When('I add a product priced at :price')]
function whenAddProduct(CartService $cart, float $price): CartService
{
    $cart->addItem(['price' => $price]);
    return $cart;
}

#[When('I apply discount code :code')]
function whenApplyDiscount(CartService $cart, string $code): array
{
    $discountService = new DiscountService();
    $result = $discountService->apply($cart, $code);
    return ['cart' => $cart, 'result' => $result];
}

#[Then('the cart total should be :expected')]
function thenTotalShouldBe(CartService $cart, float $expected): void
{
    expect($cart->total())->toBe($expected);
}

#[Then('I should see error :message')]
function thenShouldSeeError(array $context, string $message): void
{
    expect($context['result']->error)->toBe($message);
}
```

### Running Gherkin Scenarios

With pest-plugin-bdd, you can run `.feature` files directly:

```bash
pest features/shopping_cart.feature
```

```
   PASS  features/shopping_cart.feature
  ✓ Scenario: Add item to empty cart
  ✓ Scenario: Add multiple items to cart
  ✓ Scenario: Apply percentage discount
  ✓ Scenario: Apply invalid discount code

  Tests:    4 passed
  Duration: 0.08s
```

### Adding TestLink to Step Definitions

TestLink can link step definitions to production code:

```php
// tests/Steps/CartSteps.php

use TestFlowLabs\TestingAttributes\Given;
use TestFlowLabs\TestingAttributes\When;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

#[When('I add a product priced at :price')]
#[LinksAndCovers(CartService::class, 'addItem')]
function whenAddProduct(CartService $cart, float $price): CartService
{
    $cart->addItem(['price' => $price]);
    return $cart;
}

#[When('I apply discount code :code')]
#[LinksAndCovers(DiscountService::class, 'apply')]
function whenApplyDiscount(CartService $cart, string $code): array
{
    $discountService = new DiscountService();
    $result = $discountService->apply($cart, $code);
    return ['cart' => $cart, 'result' => $result];
}
```

This creates traceability from Gherkin scenarios → step definitions → production code.

---

## Traceability Layers

BDD creates multiple test layers. Use different link types for each:

| Test Layer | Link Type | Why |
|------------|-----------|-----|
| **Acceptance/Feature** | `links()` | Traces behavior, coverage tracked at unit level |
| **Integration** | `linksAndCovers()` | Both traceability and coverage |
| **Unit** | `linksAndCovers()` | Primary coverage tracking |

### Why Different Link Types?

```
Feature Test (links) ─────────────────────────────────────┐
                                                          │
Integration Test (linksAndCovers) ────────────────────┐   │
                                                      │   │
Unit Test (linksAndCovers) ───────────────────┐       │   │
                                              │       │   │
                                              ▼       ▼   ▼
                                      ┌─────────────────────┐
                                      │   Production Code   │
                                      │  CartService::add() │
                                      └─────────────────────┘
```

- **Unit tests** (`linksAndCovers`): Test isolated behavior, primary coverage
- **Integration tests** (`linksAndCovers`): Test service collaboration, additional coverage
- **Feature tests** (`links`): Verify business scenarios, no coverage (already counted)

Using `links()` at the feature level avoids counting the same code coverage multiple times.

### Example: Multi-Layer Linking

::: code-group

```php [Pest]
// tests/Unit/CartServiceTest.php - Primary coverage
test('add item increases total', function () {
    // ...
})->linksAndCovers(CartService::class.'::addItem');

// tests/Integration/CartIntegrationTest.php - Service integration
test('cart persists items across requests', function () {
    // ...
})->linksAndCovers(CartService::class.'::addItem');

// tests/Feature/ShoppingCartTest.php - Behavior verification only
test('add item to empty cart', function () {
    // ...
})->links(CartService::class.'::addItem');
```

```php [PHPUnit]
// tests/Unit/CartServiceTest.php - Primary coverage
#[Test]
#[LinksAndCovers(CartService::class, 'addItem')]
public function add_item_increases_total(): void
{
    // ...
}

// tests/Integration/CartIntegrationTest.php - Service integration
#[Test]
#[LinksAndCovers(CartService::class, 'addItem')]
public function cart_persists_items_across_requests(): void
{
    // ...
}

// tests/Feature/ShoppingCartTest.php - Behavior verification only
#[Test]
#[Links(CartService::class, 'addItem')]
public function add_item_to_empty_cart(): void
{
    // ...
}
```

:::

---

## When to Add Links

| Phase | Add Links? | Why |
|-------|------------|-----|
| Writing Gherkin scenarios | No | Don't know implementation yet |
| Writing acceptance tests | No | Design still emerging |
| Inner TDD loops | Maybe (placeholders) | Can use placeholders for speed |
| Acceptance tests pass | **Yes** | Design is now stable |
| Refactoring | Update if needed | Links should reflect current design |
| Adding new scenarios | After implementation | Same rule applies |

---

## Key Principles

1. **Scenarios first** - Write behavior descriptions without implementation details
2. **Outside-in development** - Start with acceptance tests, drive unit tests
3. **Double loop** - Outer (acceptance) and inner (unit TDD) loops work together
4. **Links after implementation** - Document what exists, not what might exist
5. **Layered traceability** - Use `links()` vs `linksAndCovers()` appropriately
6. **Placeholders for speed** - Use during development, resolve before committing

The value of TestLink in BDD is answering:
- "Which code implements this scenario?"
- "Which scenarios verify this code?"

These questions can only be answered **after implementation exists**.

::: tip CI Integration
For CI/CD integration patterns, see the [CI Integration Guide](/best-practices/ci-integration).
:::
