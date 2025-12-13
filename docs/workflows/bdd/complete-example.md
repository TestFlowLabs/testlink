# Complete BDD Example

This tutorial walks through building a complete shopping cart feature using BDD with TestLink. You'll see the full workflow from acceptance tests to implementation.

## What We're Building

A shopping cart that:
- Allows adding items
- Calculates totals
- Applies discount codes
- Processes checkout

## Project Structure

```
src/
  Cart/
    ShoppingCart.php
    CartItem.php
    DiscountCalculator.php
tests/
  Feature/
    ShoppingCartFeatureTest.php
  Unit/
    ShoppingCartTest.php
    CartItemTest.php
    DiscountCalculatorTest.php
```

## Part 1: Define Acceptance Scenarios

Start with high-level user scenarios:

:::tabs key:stack
== Pest

```php
<?php
// tests/Feature/ShoppingCartFeatureTest.php

use App\Cart\ShoppingCart;
use App\Cart\CartItem;
use App\Cart\DiscountCalculator;

describe('Shopping Cart Feature', function () {
    test('customer can add items and see total', function () {
        $cart = new ShoppingCart();

        $cart->addItem(new CartItem('SKU-001', 'Widget', 2500, 2));
        $cart->addItem(new CartItem('SKU-002', 'Gadget', 5000, 1));

        expect($cart->getItemCount())->toBe(2);
        expect($cart->getSubtotal())->toBe(10000); // (2500*2) + (5000*1)
    })
    ->links(ShoppingCart::class.'::addItem')
    ->links(ShoppingCart::class.'::getSubtotal');

    test('customer can apply discount code', function () {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'Widget', 10000, 1));

        $cart->applyDiscountCode('SAVE20'); // 20% off

        expect($cart->getTotal())->toBe(8000);
    })
    ->links(ShoppingCart::class.'::applyDiscountCode')
    ->links(ShoppingCart::class.'::getTotal');

    test('customer can remove items', function () {
        $cart = new ShoppingCart();
        $item = new CartItem('SKU-001', 'Widget', 2500, 1);

        $cart->addItem($item);
        expect($cart->getItemCount())->toBe(1);

        $cart->removeItem('SKU-001');
        expect($cart->getItemCount())->toBe(0);
    })
    ->links(ShoppingCart::class.'::removeItem');

    test('cart handles quantity updates', function () {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 1));
        $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 2)); // Add more

        expect($cart->getQuantity('SKU-001'))->toBe(3);
        expect($cart->getSubtotal())->toBe(3000);
    })
    ->links(ShoppingCart::class.'::addItem')
    ->links(ShoppingCart::class.'::getQuantity');
});
```

== PHPUnit + Attributes

```php
<?php
// tests/Feature/ShoppingCartFeatureTest.php

namespace Tests\Feature;

use App\Cart\ShoppingCart;
use App\Cart\CartItem;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\Links;

class ShoppingCartFeatureTest extends TestCase
{
    #[Links(ShoppingCart::class, 'addItem')]
    #[Links(ShoppingCart::class, 'getSubtotal')]
    public function test_customer_can_add_items_and_see_total(): void
    {
        $cart = new ShoppingCart();

        $cart->addItem(new CartItem('SKU-001', 'Widget', 2500, 2));
        $cart->addItem(new CartItem('SKU-002', 'Gadget', 5000, 1));

        $this->assertSame(2, $cart->getItemCount());
        $this->assertSame(10000, $cart->getSubtotal());
    }

    #[Links(ShoppingCart::class, 'applyDiscountCode')]
    #[Links(ShoppingCart::class, 'getTotal')]
    public function test_customer_can_apply_discount_code(): void
    {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'Widget', 10000, 1));

        $cart->applyDiscountCode('SAVE20');

        $this->assertSame(8000, $cart->getTotal());
    }

    #[Links(ShoppingCart::class, 'removeItem')]
    public function test_customer_can_remove_items(): void
    {
        $cart = new ShoppingCart();
        $item = new CartItem('SKU-001', 'Widget', 2500, 1);

        $cart->addItem($item);
        $this->assertSame(1, $cart->getItemCount());

        $cart->removeItem('SKU-001');
        $this->assertSame(0, $cart->getItemCount());
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/Feature/ShoppingCartFeatureTest.php

namespace Tests\Feature;

use App\Cart\ShoppingCart;
use App\Cart\CartItem;
use PHPUnit\Framework\TestCase;

class ShoppingCartFeatureTest extends TestCase
{
    /**
     * @see \App\Cart\ShoppingCart::addItem
     * @see \App\Cart\ShoppingCart::getSubtotal
     */
    public function test_customer_can_add_items_and_see_total(): void
    {
        $cart = new ShoppingCart();

        $cart->addItem(new CartItem('SKU-001', 'Widget', 2500, 2));
        $cart->addItem(new CartItem('SKU-002', 'Gadget', 5000, 1));

        $this->assertSame(2, $cart->getItemCount());
        $this->assertSame(10000, $cart->getSubtotal());
    }

    /**
     * @see \App\Cart\ShoppingCart::applyDiscountCode
     * @see \App\Cart\ShoppingCart::getTotal
     */
    public function test_customer_can_apply_discount_code(): void
    {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'Widget', 10000, 1));

        $cart->applyDiscountCode('SAVE20');

        $this->assertSame(8000, $cart->getTotal());
    }

    /**
     * @see \App\Cart\ShoppingCart::removeItem
     */
    public function test_customer_can_remove_items(): void
    {
        $cart = new ShoppingCart();
        $item = new CartItem('SKU-001', 'Widget', 2500, 1);

        $cart->addItem($item);
        $this->assertSame(1, $cart->getItemCount());

        $cart->removeItem('SKU-001');
        $this->assertSame(0, $cart->getItemCount());
    }
}
```

:::

## Part 2: Write Unit Tests

Now break down into unit tests:

:::tabs key:stack
== Pest

```php
<?php
// tests/Unit/ShoppingCartTest.php

use App\Cart\ShoppingCart;
use App\Cart\CartItem;

describe('ShoppingCart', function () {
    describe('addItem', function () {
        test('adds new item to cart', function () {
            $cart = new ShoppingCart();
            $item = new CartItem('SKU-001', 'Widget', 1000, 1);

            $cart->addItem($item);

            expect($cart->hasItem('SKU-001'))->toBeTrue();
        })->linksAndCovers(ShoppingCart::class.'::addItem');

        test('increases quantity when adding existing SKU', function () {
            $cart = new ShoppingCart();

            $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 1));
            $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 2));

            expect($cart->getQuantity('SKU-001'))->toBe(3);
        })->linksAndCovers(ShoppingCart::class.'::addItem');
    });

    describe('removeItem', function () {
        test('removes item by SKU', function () {
            $cart = new ShoppingCart();
            $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 1));

            $cart->removeItem('SKU-001');

            expect($cart->hasItem('SKU-001'))->toBeFalse();
        })->linksAndCovers(ShoppingCart::class.'::removeItem');

        test('throws exception for non-existent SKU', function () {
            $cart = new ShoppingCart();

            expect(fn () => $cart->removeItem('INVALID'))
                ->toThrow(\InvalidArgumentException::class);
        })->linksAndCovers(ShoppingCart::class.'::removeItem');
    });

    describe('getSubtotal', function () {
        test('calculates sum of all item totals', function () {
            $cart = new ShoppingCart();
            $cart->addItem(new CartItem('SKU-001', 'A', 1000, 2)); // 2000
            $cart->addItem(new CartItem('SKU-002', 'B', 500, 3));  // 1500

            expect($cart->getSubtotal())->toBe(3500);
        })->linksAndCovers(ShoppingCart::class.'::getSubtotal');

        test('returns zero for empty cart', function () {
            $cart = new ShoppingCart();

            expect($cart->getSubtotal())->toBe(0);
        })->linksAndCovers(ShoppingCart::class.'::getSubtotal');
    });

    describe('applyDiscountCode', function () {
        test('stores valid discount code', function () {
            $cart = new ShoppingCart();

            $cart->applyDiscountCode('SAVE20');

            expect($cart->getDiscountCode())->toBe('SAVE20');
        })->linksAndCovers(ShoppingCart::class.'::applyDiscountCode');

        test('throws for invalid discount code', function () {
            $cart = new ShoppingCart();

            expect(fn () => $cart->applyDiscountCode('INVALID'))
                ->toThrow(\InvalidArgumentException::class);
        })->linksAndCovers(ShoppingCart::class.'::applyDiscountCode');
    });

    describe('getTotal', function () {
        test('returns subtotal when no discount', function () {
            $cart = new ShoppingCart();
            $cart->addItem(new CartItem('SKU-001', 'Widget', 10000, 1));

            expect($cart->getTotal())->toBe(10000);
        })->linksAndCovers(ShoppingCart::class.'::getTotal');

        test('applies percentage discount', function () {
            $cart = new ShoppingCart();
            $cart->addItem(new CartItem('SKU-001', 'Widget', 10000, 1));
            $cart->applyDiscountCode('SAVE20'); // 20% off

            expect($cart->getTotal())->toBe(8000);
        })->linksAndCovers(ShoppingCart::class.'::getTotal');
    });
});
```

== PHPUnit + Attributes

```php
<?php
// tests/Unit/ShoppingCartTest.php

namespace Tests\Unit;

use App\Cart\ShoppingCart;
use App\Cart\CartItem;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class ShoppingCartTest extends TestCase
{
    #[LinksAndCovers(ShoppingCart::class, 'addItem')]
    public function test_adds_new_item_to_cart(): void
    {
        $cart = new ShoppingCart();
        $item = new CartItem('SKU-001', 'Widget', 1000, 1);

        $cart->addItem($item);

        $this->assertTrue($cart->hasItem('SKU-001'));
    }

    #[LinksAndCovers(ShoppingCart::class, 'addItem')]
    public function test_increases_quantity_when_adding_existing_sku(): void
    {
        $cart = new ShoppingCart();

        $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 1));
        $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 2));

        $this->assertSame(3, $cart->getQuantity('SKU-001'));
    }

    #[LinksAndCovers(ShoppingCart::class, 'removeItem')]
    public function test_removes_item_by_sku(): void
    {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 1));

        $cart->removeItem('SKU-001');

        $this->assertFalse($cart->hasItem('SKU-001'));
    }

    #[LinksAndCovers(ShoppingCart::class, 'getSubtotal')]
    public function test_calculates_sum_of_all_item_totals(): void
    {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'A', 1000, 2));
        $cart->addItem(new CartItem('SKU-002', 'B', 500, 3));

        $this->assertSame(3500, $cart->getSubtotal());
    }

    #[LinksAndCovers(ShoppingCart::class, 'getTotal')]
    public function test_applies_percentage_discount(): void
    {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'Widget', 10000, 1));
        $cart->applyDiscountCode('SAVE20');

        $this->assertSame(8000, $cart->getTotal());
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/Unit/ShoppingCartTest.php

namespace Tests\Unit;

use App\Cart\ShoppingCart;
use App\Cart\CartItem;
use PHPUnit\Framework\TestCase;

class ShoppingCartTest extends TestCase
{
    /**
     * @see \App\Cart\ShoppingCart::addItem
     */
    public function test_adds_new_item_to_cart(): void
    {
        $cart = new ShoppingCart();
        $item = new CartItem('SKU-001', 'Widget', 1000, 1);

        $cart->addItem($item);

        $this->assertTrue($cart->hasItem('SKU-001'));
    }

    /**
     * @see \App\Cart\ShoppingCart::addItem
     */
    public function test_increases_quantity_when_adding_existing_sku(): void
    {
        $cart = new ShoppingCart();

        $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 1));
        $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 2));

        $this->assertSame(3, $cart->getQuantity('SKU-001'));
    }

    /**
     * @see \App\Cart\ShoppingCart::removeItem
     */
    public function test_removes_item_by_sku(): void
    {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'Widget', 1000, 1));

        $cart->removeItem('SKU-001');

        $this->assertFalse($cart->hasItem('SKU-001'));
    }

    /**
     * @see \App\Cart\ShoppingCart::getSubtotal
     */
    public function test_calculates_sum_of_all_item_totals(): void
    {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'A', 1000, 2));
        $cart->addItem(new CartItem('SKU-002', 'B', 500, 3));

        $this->assertSame(3500, $cart->getSubtotal());
    }

    /**
     * @see \App\Cart\ShoppingCart::getTotal
     */
    public function test_applies_percentage_discount(): void
    {
        $cart = new ShoppingCart();
        $cart->addItem(new CartItem('SKU-001', 'Widget', 10000, 1));
        $cart->applyDiscountCode('SAVE20');

        $this->assertSame(8000, $cart->getTotal());
    }
}
```

:::

## Part 3: Implement the Classes

### CartItem

```php
<?php
// src/Cart/CartItem.php

namespace App\Cart;

class CartItem
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly int $priceInCents,
        public int $quantity = 1
    ) {}

    public function getTotalPrice(): int
    {
        return $this->priceInCents * $this->quantity;
    }

    public function addQuantity(int $amount): void
    {
        $this->quantity += $amount;
    }
}
```

### DiscountCalculator

```php
<?php
// src/Cart/DiscountCalculator.php

namespace App\Cart;

use TestFlowLabs\TestingAttributes\TestedBy;

class DiscountCalculator
{
    private const DISCOUNT_CODES = [
        'SAVE10' => 0.10,
        'SAVE20' => 0.20,
        'HALF' => 0.50,
    ];

    #[TestedBy('Tests\Unit\DiscountCalculatorTest', 'returns true for valid code')]
    #[TestedBy('Tests\Unit\DiscountCalculatorTest', 'returns false for invalid code')]
    public function isValidCode(string $code): bool
    {
        return isset(self::DISCOUNT_CODES[$code]);
    }

    #[TestedBy('Tests\Unit\DiscountCalculatorTest', 'calculates percentage discount')]
    public function calculate(int $subtotal, string $code): int
    {
        if (!$this->isValidCode($code)) {
            return $subtotal;
        }

        $discount = self::DISCOUNT_CODES[$code];

        return (int) ($subtotal * (1 - $discount));
    }
}
```

### ShoppingCart

```php
<?php
// src/Cart/ShoppingCart.php

namespace App\Cart;

use TestFlowLabs\TestingAttributes\TestedBy;

class ShoppingCart
{
    private array $items = [];
    private ?string $discountCode = null;
    private DiscountCalculator $discountCalculator;

    public function __construct(?DiscountCalculator $discountCalculator = null)
    {
        $this->discountCalculator = $discountCalculator ?? new DiscountCalculator();
    }

    #[TestedBy('Tests\Feature\ShoppingCartFeatureTest', 'customer can add items and see total')]
    #[TestedBy('Tests\Feature\ShoppingCartFeatureTest', 'cart handles quantity updates')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'adds new item to cart')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'increases quantity when adding existing SKU')]
    public function addItem(CartItem $item): void
    {
        if (isset($this->items[$item->sku])) {
            $this->items[$item->sku]->addQuantity($item->quantity);
        } else {
            $this->items[$item->sku] = $item;
        }
    }

    #[TestedBy('Tests\Feature\ShoppingCartFeatureTest', 'customer can remove items')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'removes item by SKU')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'throws exception for non-existent SKU')]
    public function removeItem(string $sku): void
    {
        if (!isset($this->items[$sku])) {
            throw new \InvalidArgumentException("Item {$sku} not in cart");
        }

        unset($this->items[$sku]);
    }

    #[TestedBy('Tests\Feature\ShoppingCartFeatureTest', 'customer can add items and see total')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'calculates sum of all item totals')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'returns zero for empty cart')]
    public function getSubtotal(): int
    {
        return array_reduce(
            $this->items,
            fn ($sum, $item) => $sum + $item->getTotalPrice(),
            0
        );
    }

    #[TestedBy('Tests\Feature\ShoppingCartFeatureTest', 'customer can apply discount code')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'stores valid discount code')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'throws for invalid discount code')]
    public function applyDiscountCode(string $code): void
    {
        if (!$this->discountCalculator->isValidCode($code)) {
            throw new \InvalidArgumentException("Invalid discount code: {$code}");
        }

        $this->discountCode = $code;
    }

    #[TestedBy('Tests\Feature\ShoppingCartFeatureTest', 'customer can apply discount code')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'returns subtotal when no discount')]
    #[TestedBy('Tests\Unit\ShoppingCartTest', 'applies percentage discount')]
    public function getTotal(): int
    {
        $subtotal = $this->getSubtotal();

        if ($this->discountCode === null) {
            return $subtotal;
        }

        return $this->discountCalculator->calculate($subtotal, $this->discountCode);
    }

    public function hasItem(string $sku): bool
    {
        return isset($this->items[$sku]);
    }

    public function getQuantity(string $sku): int
    {
        return $this->items[$sku]?->quantity ?? 0;
    }

    public function getItemCount(): int
    {
        return count($this->items);
    }

    public function getDiscountCode(): ?string
    {
        return $this->discountCode;
    }
}
```

## Part 4: Validate and Report

### Run All Tests

```bash
./vendor/bin/pest

# Feature
# ✓ customer can add items and see total
# ✓ customer can apply discount code
# ✓ customer can remove items
# ✓ cart handles quantity updates

# Unit
# ✓ adds new item to cart
# ✓ increases quantity when adding existing SKU
# ✓ removes item by SKU
# ✓ throws exception for non-existent SKU
# ✓ calculates sum of all item totals
# ✓ returns zero for empty cart
# ✓ stores valid discount code
# ✓ throws for invalid discount code
# ✓ returns subtotal when no discount
# ✓ applies percentage discount
```

### Validate Links

```bash
./vendor/bin/testlink validate
```

```
Validation Report
─────────────────

Link Summary
  Feature test links: 7
  Unit test links: 10
  Total links: 17

✓ All links are valid!
```

### View Complete Report

```bash
./vendor/bin/testlink report
```

```
Coverage Links Report
─────────────────────

App\Cart\ShoppingCart

  addItem()
    → Tests\Feature\ShoppingCartFeatureTest::customer can add items and see total
    → Tests\Feature\ShoppingCartFeatureTest::cart handles quantity updates
    → Tests\Unit\ShoppingCartTest::adds new item to cart
    → Tests\Unit\ShoppingCartTest::increases quantity when adding existing SKU

  removeItem()
    → Tests\Feature\ShoppingCartFeatureTest::customer can remove items
    → Tests\Unit\ShoppingCartTest::removes item by SKU
    → Tests\Unit\ShoppingCartTest::throws exception for non-existent SKU

  getSubtotal()
    → Tests\Feature\ShoppingCartFeatureTest::customer can add items and see total
    → Tests\Unit\ShoppingCartTest::calculates sum of all item totals
    → Tests\Unit\ShoppingCartTest::returns zero for empty cart

  applyDiscountCode()
    → Tests\Feature\ShoppingCartFeatureTest::customer can apply discount code
    → Tests\Unit\ShoppingCartTest::stores valid discount code
    → Tests\Unit\ShoppingCartTest::throws for invalid discount code

  getTotal()
    → Tests\Feature\ShoppingCartFeatureTest::customer can apply discount code
    → Tests\Unit\ShoppingCartTest::returns subtotal when no discount
    → Tests\Unit\ShoppingCartTest::applies percentage discount

App\Cart\DiscountCalculator

  isValidCode()
    → Tests\Unit\DiscountCalculatorTest::returns true for valid code
    → Tests\Unit\DiscountCalculatorTest::returns false for invalid code

  calculate()
    → Tests\Unit\DiscountCalculatorTest::calculates percentage discount

Summary
  Methods with tests: 7
  Total test links: 17
```

## What You Learned

1. **BDD structure** - Acceptance tests drive unit tests
2. **links() vs linksAndCovers()** - Use `links()` for acceptance, `linksAndCovers()` for unit
3. **Double-loop workflow** - Outer acceptance loop, inner unit loop
4. **Complete traceability** - From user stories to implementation

## What's Next?

- [Run Validation in CI](/how-to/run-validation-in-ci) - Automate validation
- [Explanation: TDD Deep Dive](/explanation/tdd/) - Understand the theory
- [Explanation: BDD Deep Dive](/explanation/bdd/) - Advanced BDD concepts
