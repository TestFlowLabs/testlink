# Acceptance vs Unit Links

Understanding the difference between how acceptance tests and unit tests link to production code.

## Two Kinds of Tests

In BDD, you have two distinct test types:

### Acceptance Tests

Test user-facing behavior:

```php
test('user can purchase product', function () {
    // Tests the entire flow from user perspective
    $user = createUser();
    $product = createProduct(['price' => 100]);

    login($user);
    addToCart($product);
    checkout();

    expect(orderExists())->toBeTrue();
    expect(lastOrder()->total)->toBe(100);
});
```

### Unit Tests

Test specific implementation details:

```php
test('calculates order total with tax', function () {
    // Tests one specific calculation
    $order = new Order([
        new LineItem(100),
        new LineItem(50),
    ]);

    expect($order->calculateTotal(0.1))->toBe(165); // 150 + 15 tax
});
```

## Different Linking Strategies

These test types should link differently:

| Test Type | Link Method | Why |
|-----------|-------------|-----|
| Acceptance | `links()` | Exercises but doesn't claim primary coverage |
| Unit | `linksAndCovers()` | Primary verification of specific behavior |

### Why the Difference?

**Acceptance tests touch many methods**:

```php
test('user can purchase product', function () {
    // This test exercises:
    // - UserService::create
    // - ProductService::find
    // - CartService::add
    // - CartService::checkout
    // - OrderService::create
    // - PaymentService::process
    // ... and more
});
```

If this test `linksAndCovers()` all of them, it would:
- Inflate coverage metrics
- Claim to be the primary test for everything
- Make it hard to identify true unit test coverage

**Unit tests focus on one thing**:

```php
test('calculates total with tax', function () {
    // This test verifies exactly one method's behavior
})->linksAndCovers(Order::class.'::calculateTotal');
```

This test IS the primary verification for `calculateTotal()`.

## Linking Acceptance Tests

Use `links()` for traceability without coverage:

```php
test('user can purchase product', function () {
    // High-level feature test
})
->links(CartService::class.'::add')
->links(CartService::class.'::checkout')
->links(OrderService::class.'::create')
->links(PaymentService::class.'::process');
```

### What `links()` Provides

1. **Traceability**: "This feature touches these methods"
2. **Navigation**: IDE can jump to linked code
3. **Documentation**: Shows which features use which code
4. **No Coverage Claim**: Doesn't affect coverage metrics

### Report Output

```bash
./vendor/bin/testlink report

CartService
└── add()
    ├── CartServiceTest::test_adds_item (covers)     ← Unit test
    └── PurchaseFlowTest::test_can_purchase (links)  ← Acceptance test
```

## Linking Unit Tests

Use `linksAndCovers()` (Pest) or `#[LinksAndCovers]` (PHPUnit) for primary coverage:

```php
test('adds item to cart', function () {
    $cart = new Cart();
    $cart->add(new Product(['price' => 100]));

    expect($cart->items())->toHaveCount(1);
})->linksAndCovers(Cart::class.'::add');
```

### What `linksAndCovers()` Provides

1. **Coverage**: This test verifies this method
2. **Bidirectional Link**: Production can have `#[TestedBy]`
3. **Validation**: TestLink checks link integrity
4. **Primary Ownership**: This test "owns" this method's verification

## The Complete Picture

After BDD development with proper linking:

```
Acceptance Tests                Unit Tests
────────────────                ──────────
user can checkout     ────────► CartService::add
     │                              ↑ linksAndCovers
     │ links                        │
     │                          adds item to cart
     │
     │               ────────► CartService::calculateTotal
     │                              ↑ linksAndCovers
     │ links                        │
     │                          calculates total with discount
     │
     │               ────────► PaymentService::process
     │                              ↑ linksAndCovers
     │ links                        │
     │                          processes valid payment
     ▼
(traceability only)         (primary coverage)
```

## Practical Example

### Feature: Shopping Cart Discount

**Acceptance Test**:

```php
// tests/Feature/CartDiscountTest.php
test('premium user sees discount applied', function () {
    $user = User::factory()->premium()->create();
    $product = Product::factory()->create(['price' => 100]);

    actingAs($user);
    addToCart($product);

    expect(cartTotal())->toBe(90);  // 10% premium discount
})
->links(Cart::class.'::add')
->links(Cart::class.'::calculateTotal')
->links(DiscountService::class.'::applyPremiumDiscount');
```

**Unit Tests**:

```php
// tests/Unit/CartTest.php
test('adds item to cart', function () {
    $cart = new Cart();
    $cart->add(new Product(['price' => 100]));

    expect($cart->items())->toHaveCount(1);
})->linksAndCovers(Cart::class.'::add');

test('calculates total', function () {
    $cart = new Cart();
    $cart->add(new Product(['price' => 100]));
    $cart->add(new Product(['price' => 50]));

    expect($cart->calculateTotal())->toBe(150);
})->linksAndCovers(Cart::class.'::calculateTotal');
```

```php
// tests/Unit/DiscountServiceTest.php
test('applies premium discount', function () {
    $service = new DiscountService();
    $discounted = $service->applyPremiumDiscount(100);

    expect($discounted)->toBe(90);
})->linksAndCovers(DiscountService::class.'::applyPremiumDiscount');
```

### Report Shows Both Layers

```bash
./vendor/bin/testlink report

Cart
├── add()
│   ├── CartTest::adds item to cart (covers)
│   └── CartDiscountTest::premium user sees discount (links)
└── calculateTotal()
    ├── CartTest::calculates total (covers)
    └── CartDiscountTest::premium user sees discount (links)

DiscountService
└── applyPremiumDiscount()
    ├── DiscountServiceTest::applies premium discount (covers)
    └── CartDiscountTest::premium user sees discount (links)
```

## When to Use Each

### Use `links()` For

- Acceptance/feature tests
- Integration tests
- E2E tests
- Any test that exercises code as part of a larger flow
- Tests where the method being called isn't the primary focus

### Use `linksAndCovers()` / `#[LinksAndCovers]` For

- Unit tests
- Tests that specifically verify one method's behavior
- Tests that would fail if that exact method is broken
- The "primary" test for a piece of code

## Common Patterns

### Pattern 1: Feature Linking

```php
// Feature test links to all methods it touches
test('user registration flow', function () {
    // ...
})
->links(UserService::class.'::create')
->links(EmailService::class.'::sendVerification')
->links(ProfileService::class.'::createDefault');
```

### Pattern 2: Unit Covering

```php
// Each unit test covers exactly one method
test('creates user', fn() => ...)->linksAndCovers(UserService::class.'::create');
test('sends verification', fn() => ...)->linksAndCovers(EmailService::class.'::sendVerification');
test('creates profile', fn() => ...)->linksAndCovers(ProfileService::class.'::createDefault');
```

### Pattern 3: Mixed Test

```php
// Test primarily covers one method, links to helpers
test('processes payment with logging', function () {
    // Primary focus is processing
    // Logging is a side effect
})
->linksAndCovers(PaymentService::class.'::process')  // Primary
->links(LogService::class.'::log');                   // Secondary
```

## Summary

| Aspect | Acceptance Tests | Unit Tests |
|--------|-----------------|------------|
| Focus | User behavior | Implementation detail |
| Scope | Many methods | One method |
| Link Type | `links()` | `linksAndCovers()` |
| Coverage | No | Yes |
| Purpose | Traceability | Primary verification |

This separation keeps coverage metrics meaningful while still providing full traceability from features to implementation.

## See Also

- [Links vs LinksAndCovers](/explanation/links-vs-linksandcovers)
- [BDD and TestLink](./index)
- [#[Links] Reference](/reference/attributes/links)
- [#[LinksAndCovers] Reference](/reference/attributes/linksandcovers)
