# Placeholder BDD

This tutorial shows how to use placeholders during BDD workflows. Placeholders let you describe behaviors before knowing the final implementation details.

## Why Placeholders in BDD?

In BDD, you often start with behavior descriptions before knowing:
- Class names
- Method names
- Implementation details

Placeholders let you maintain traceability while keeping options open.

## BDD Placeholder Workflow

```
1. Write acceptance test with behavior placeholder (@feature-name)
2. Write unit tests with same placeholder
3. Implement code with matching placeholder
4. Resolve placeholders when design is stable
```

## Tutorial: Shopping Cart Feature

Let's build a shopping cart using placeholder-based BDD.

### Step 1: Define Behaviors with Placeholders

Start with acceptance tests using behavior-focused placeholders:

::: code-group

```php [Pest]
<?php
// tests/Feature/ShoppingCartTest.php

describe('Shopping Cart', function () {
    test('user can add items to cart', function () {
        // We don't know the exact class structure yet
        $cart = createCart();
        $item = createItem('Widget', 1500);

        addToCart($cart, $item);

        expect(cartItemCount($cart))->toBe(1);
    })->links('@cart-add');

    test('user can see cart total', function () {
        $cart = createCart();

        addToCart($cart, createItem('A', 1000));
        addToCart($cart, createItem('B', 2000));

        expect(cartTotal($cart))->toBe(3000);
    })->links('@cart-total');

    test('user can remove items from cart', function () {
        $cart = createCart();
        $item = createItem('Widget', 1500);

        addToCart($cart, $item);
        removeFromCart($cart, $item);

        expect(cartItemCount($cart))->toBe(0);
    })->links('@cart-remove');
});
```

```php [PHPUnit]
<?php
// tests/Feature/ShoppingCartTest.php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\Links;

class ShoppingCartTest extends TestCase
{
    #[Links('@cart-add')]
    public function test_user_can_add_items_to_cart(): void
    {
        $cart = $this->createCart();
        $item = $this->createItem('Widget', 1500);

        $this->addToCart($cart, $item);

        $this->assertSame(1, $this->cartItemCount($cart));
    }

    #[Links('@cart-total')]
    public function test_user_can_see_cart_total(): void
    {
        $cart = $this->createCart();

        $this->addToCart($cart, $this->createItem('A', 1000));
        $this->addToCart($cart, $this->createItem('B', 2000));

        $this->assertSame(3000, $this->cartTotal($cart));
    }

    #[Links('@cart-remove')]
    public function test_user_can_remove_items_from_cart(): void
    {
        $cart = $this->createCart();
        $item = $this->createItem('Widget', 1500);

        $this->addToCart($cart, $item);
        $this->removeFromCart($cart, $item);

        $this->assertSame(0, $this->cartItemCount($cart));
    }
}
```

:::

### Step 2: Add Unit Tests with Same Placeholders

::: code-group

```php [Pest]
<?php
// tests/Unit/CartTest.php

describe('Cart Implementation', function () {
    describe('add item', function () {
        test('stores item in collection', function () {
            // Implementation will be determined by inner loop
        })->linksAndCovers('@cart-add');

        test('increments quantity for duplicate items', function () {
            // ...
        })->linksAndCovers('@cart-add');
    });

    describe('calculate total', function () {
        test('sums all item prices', function () {
            // ...
        })->linksAndCovers('@cart-total');

        test('returns zero for empty cart', function () {
            // ...
        })->linksAndCovers('@cart-total');
    });

    describe('remove item', function () {
        test('removes item from collection', function () {
            // ...
        })->linksAndCovers('@cart-remove');

        test('throws if item not in cart', function () {
            // ...
        })->linksAndCovers('@cart-remove');
    });
});
```

```php [PHPUnit]
<?php
// tests/Unit/CartTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class CartTest extends TestCase
{
    #[LinksAndCovers('@cart-add')]
    public function test_stores_item_in_collection(): void
    {
        // Implementation will be determined by inner loop
    }

    #[LinksAndCovers('@cart-add')]
    public function test_increments_quantity_for_duplicate_items(): void
    {
        // ...
    }

    #[LinksAndCovers('@cart-total')]
    public function test_sums_all_item_prices(): void
    {
        // ...
    }

    #[LinksAndCovers('@cart-remove')]
    public function test_removes_item_from_collection(): void
    {
        // ...
    }
}
```

:::

### Step 3: Implement with Matching Placeholders

```php
<?php
// src/Cart/ShoppingCart.php

namespace App\Cart;

use TestFlowLabs\TestingAttributes\TestedBy;

class ShoppingCart
{
    private array $items = [];

    #[TestedBy('@cart-add')]
    public function addItem(CartItem $item): void
    {
        $key = $item->productId;

        if (isset($this->items[$key])) {
            $this->items[$key]->incrementQuantity();
        } else {
            $this->items[$key] = $item;
        }
    }

    #[TestedBy('@cart-remove')]
    public function removeItem(CartItem $item): void
    {
        $key = $item->productId;

        if (!isset($this->items[$key])) {
            throw new \InvalidArgumentException('Item not in cart');
        }

        unset($this->items[$key]);
    }

    #[TestedBy('@cart-total')]
    public function getTotal(): int
    {
        return array_reduce(
            $this->items,
            fn ($sum, $item) => $sum + $item->getTotalPrice(),
            0
        );
    }

    public function getItemCount(): int
    {
        return count($this->items);
    }
}
```

### Step 4: Preview Placeholder Pairing

```bash
./vendor/bin/testlink pair --dry-run
```

```
Pairing Placeholders
────────────────────
Running in dry-run mode.

Found Placeholders
  ✓ @cart-add     1 production × 3 tests = 3 links
  ✓ @cart-total   1 production × 3 tests = 3 links
  ✓ @cart-remove  1 production × 3 tests = 3 links

Production Files
  src/Cart/ShoppingCart.php
    @cart-add → Tests\Feature\ShoppingCartTest::test_user_can_add_items_to_cart
    @cart-add → Tests\Unit\CartTest::test_stores_item_in_collection
    @cart-add → Tests\Unit\CartTest::test_increments_quantity_for_duplicate_items
    ...

Test Files
  tests/Feature/ShoppingCartTest.php
    @cart-add → App\Cart\ShoppingCart::addItem
  tests/Unit/CartTest.php
    @cart-add → App\Cart\ShoppingCart::addItem
    ...

Would modify 3 file(s) with 9 change(s).
```

### Step 5: Resolve When Ready

Once you're happy with the implementation:

```bash
./vendor/bin/testlink pair
```

## N:M Matching in BDD

Placeholders create N:M relationships naturally:

```
Placeholder @cart-add matches:

Production (N=1):
  └── ShoppingCart::addItem

Tests (M=3):
  ├── Feature: user can add items to cart
  ├── Unit: stores item in collection
  └── Unit: increments quantity for duplicate items

Result: 1 × 3 = 3 links created
```

## Placeholder Naming Strategies

### By Feature

```php
#[TestedBy('@checkout')]
#[TestedBy('@inventory')]
#[TestedBy('@notification')]
```

### By User Story

```php
#[TestedBy('@user-can-add-to-cart')]
#[TestedBy('@user-can-checkout')]
```

### By Acceptance Criteria

```php
#[TestedBy('@AC1-cart-shows-items')]
#[TestedBy('@AC2-cart-calculates-total')]
```

## Best Practices

### 1. Use Descriptive Placeholders

```php
// Good - describes behavior
->links('@user-adds-item-to-cart')

// Less clear - describes implementation
->links('@cart-add-method')
```

### 2. Group Related Behaviors

```php
// All checkout-related behaviors
#[TestedBy('@checkout-validate')]
#[TestedBy('@checkout-payment')]
#[TestedBy('@checkout-confirmation')]
```

### 3. Resolve Before Merging

Always resolve placeholders before merging to main:

```bash
# In CI
./vendor/bin/testlink validate

# Error: Found unresolved placeholder @cart-add
```

## What's Next?

- [Complete Example](./complete-example) - Full BDD shopping cart example
- [Handle N:M Relationships](/how-to/handle-nm-relationships) - Advanced placeholder patterns
