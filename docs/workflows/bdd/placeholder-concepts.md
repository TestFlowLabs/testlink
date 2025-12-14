# Placeholders in BDD

How to use placeholders effectively in BDD's outside-in workflow.

## The BDD Timing Challenge

In BDD, you write the acceptance test first:

```php
test('user can purchase product', function () {
    // I want to test this feature...
    // But CartService doesn't exist yet!
    // And neither does OrderService or PaymentService!
});
```

This is the classic "outside-in" challenge: you're describing behavior before the implementation exists.

## Placeholders Enable Outside-In

Placeholders let you express intent without requiring classes to exist:

```php
// Acceptance test with placeholder
test('user can purchase product', function () {
    // Feature description
})->links('@purchase-flow');

// Later, when implementing:
#[TestedBy('@purchase-flow')]
public function checkout(): Order
```

## BDD Placeholder Workflow

### Phase 1: Acceptance Test (Outer Loop)

Start with the feature you want to build:

```php
// tests/Feature/CheckoutTest.php
test('customer can complete checkout', function () {
    $customer = new Customer(['email' => 'test@example.com']);
    $product = new Product(['price' => 100, 'name' => 'Widget']);

    $cart = new Cart($customer);
    $cart->add($product);

    $order = $cart->checkout();

    expect($order)->toBeInstanceOf(Order::class);
    expect($order->total)->toBe(100);
})
->links('@checkout');  // Placeholder for the entire flow
```

This test:
- Describes the desired behavior
- Uses a placeholder since classes don't exist
- Will fail (outer RED)

### Phase 2: Unit Tests (Inner Loop)

Now implement pieces with their own placeholders:

```php
// tests/Unit/CartTest.php
test('adds product to cart', function () {
    $cart = new Cart(new Customer([]));
    $cart->add(new Product(['price' => 50]));

    expect($cart->items())->toHaveCount(1);
})->linksAndCovers('@cart-add');

test('calculates total', function () {
    $cart = new Cart(new Customer([]));
    $cart->add(new Product(['price' => 100]));
    $cart->add(new Product(['price' => 50]));

    expect($cart->total())->toBe(150);
})->linksAndCovers('@cart-total');

test('creates order on checkout', function () {
    $cart = new Cart(new Customer([]));
    $cart->add(new Product(['price' => 100]));

    $order = $cart->checkout();

    expect($order->total)->toBe(100);
})->linksAndCovers('@cart-checkout');
```

### Phase 3: Implementation

```php
// src/Cart.php
class Cart
{
    private array $items = [];

    public function __construct(
        private Customer $customer
    ) {}

    #[TestedBy('@cart-add')]
    #[TestedBy('@checkout')]  // Acceptance test also links here
    public function add(Product $product): void
    {
        $this->items[] = $product;
    }

    #[TestedBy('@cart-total')]
    public function total(): int
    {
        return array_sum(array_map(fn($p) => $p->price, $this->items));
    }

    #[TestedBy('@cart-checkout')]
    #[TestedBy('@checkout')]  // Acceptance test also links here
    public function checkout(): Order
    {
        return new Order([
            'customer' => $this->customer,
            'total' => $this->total(),
            'items' => $this->items,
        ]);
    }
}
```

### Phase 4: Resolve Placeholders

```bash
./vendor/bin/testlink pair
```

After resolution:

```php
// Acceptance test
test('customer can complete checkout', function () {
    // ...
})
->links(Cart::class.'::add')
->links(Cart::class.'::checkout');

// Unit tests
test('adds product to cart', fn() => ...)
    ->linksAndCovers(Cart::class.'::add');

test('creates order on checkout', fn() => ...)
    ->linksAndCovers(Cart::class.'::checkout');
```

## Placeholder Strategies for BDD

### Strategy 1: One Placeholder Per Feature

Use one placeholder for the entire feature:

```php
// Acceptance test
test('customer can checkout')->links('@checkout');

// All related production code
#[TestedBy('@checkout')]
public function add(): void

#[TestedBy('@checkout')]
public function checkout(): Order
```

**Pros**: Simple, feature-focused
**Cons**: Less granular, all methods get same link

### Strategy 2: Layered Placeholders

Different placeholders for different layers:

```php
// Acceptance test (feature level)
test('customer can checkout')->links('@feature-checkout');

// Unit tests (implementation level)
test('adds to cart')->linksAndCovers('@unit-cart-add');
test('calculates total')->linksAndCovers('@unit-cart-total');
test('creates order')->linksAndCovers('@unit-cart-checkout');

// Production code has both
#[TestedBy('@feature-checkout')]
#[TestedBy('@unit-cart-add')]
public function add(): void
```

**Pros**: Clear separation of layers
**Cons**: More placeholders to manage

### Strategy 3: Behavior-Named Placeholders

Name placeholders after the behavior they test:

```php
// Acceptance
test('customer can checkout')->links('@can-checkout');
test('customer sees order confirmation')->links('@sees-confirmation');

// Unit (more specific)
test('validates cart not empty')->linksAndCovers('@validates-cart');
test('calculates with discount')->linksAndCovers('@applies-discount');
```

**Pros**: Self-documenting, behavior-focused
**Cons**: Requires consistent naming

## N:M Matching in BDD

BDD often has N:M relationships:

```
1 Acceptance Test  ────►  N Production Methods
M Unit Tests       ────►  1 Production Method
```

Placeholders handle this naturally:

```php
// One acceptance test, many methods
test('checkout flow')
    ->links('@checkout')  // Links to ALL methods with @checkout

// Multiple unit tests, one method
test('adds item')->linksAndCovers('@cart-add');
test('adds duplicate item')->linksAndCovers('@cart-add');
test('adds with quantity')->linksAndCovers('@cart-add');

// Production
#[TestedBy('@checkout')]     // Acceptance
#[TestedBy('@cart-add')]     // All three unit tests
public function add(): void
```

## Placeholder Resolution Order

In BDD, resolve in this order:

### 1. After Inner Loop Completion

When unit tests pass:

```bash
./vendor/bin/testlink pair --placeholder=@cart-add
./vendor/bin/testlink pair --placeholder=@cart-total
```

### 2. When Acceptance Test Passes

Feature is complete:

```bash
./vendor/bin/testlink pair --placeholder=@checkout
```

### 3. Or All at Once

```bash
./vendor/bin/testlink pair
```

## Visualizing BDD Links

After full BDD cycle:

```bash
./vendor/bin/testlink report

Cart
├── add()
│   ├── CartTest::adds product to cart (covers)
│   ├── CartTest::adds duplicate item (covers)
│   └── CheckoutTest::customer can checkout (links)
├── total()
│   ├── CartTest::calculates total (covers)
│   └── CheckoutTest::customer can checkout (links)
└── checkout()
    ├── CartTest::creates order on checkout (covers)
    └── CheckoutTest::customer can checkout (links)
```

This shows:
- Unit tests provide coverage
- Acceptance test links to exercised code
- Complete traceability from feature to implementation

## Common BDD Placeholder Patterns

### Pattern 1: Feature Umbrella

```php
// Acceptance umbrella
->links('@user-registration')

// Units under umbrella
->linksAndCovers('@user-registration')  // All use same placeholder
```

### Pattern 2: Step-Based

```php
// Each BDD step has its placeholder
test('user fills form')->links('@registration-form');
test('user submits form')->links('@registration-submit');
test('user receives email')->links('@registration-email');
```

### Pattern 3: Mixed Granularity

```php
// Feature uses broad placeholder
test('complete registration')->links('@registration');

// Critical paths use specific placeholders
test('validates unique email')->linksAndCovers('@email-unique');
```

## Best Practices

### 1. Start with Acceptance Placeholders

Write acceptance tests first with placeholders:

```php
test('feature description')->links('@feature-name');
```

### 2. Use Descriptive Names

Placeholders should be readable:

```php
// Good
->links('@user-can-checkout')
->links('@premium-discount-applied')

// Avoid
->links('@A')
->links('@test1')
```

### 3. Resolve Regularly

Don't let placeholders accumulate:

```bash
# After each feature
./vendor/bin/testlink pair
```

### 4. Validate in CI

Ensure no placeholders slip through:

```yaml
- run: ./vendor/bin/testlink pair
- run: ./vendor/bin/testlink validate
```

## Summary

Placeholders enable true outside-in BDD by:

1. Letting you write acceptance tests before implementation exists
2. Connecting the outer loop (acceptance) to inner loop (unit)
3. Creating full traceability when resolved
4. Supporting N:M relationships naturally

The key is using `links()` / `#[Links]` for acceptance tests (traceability) and `linksAndCovers()` / `#[LinksAndCovers]` for unit tests (coverage).

## See Also

- [Acceptance vs Unit Links](./acceptance-vs-unit)
- [Placeholder Strategy](/explanation/placeholder-strategy)
- [Tutorial: BDD Complete Example](/workflows/bdd/complete-example)
- [How-to: Handle N:M Relationships](/how-to/handle-nm-relationships)
