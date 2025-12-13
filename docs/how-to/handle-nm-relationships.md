# Handle N:M Relationships

This guide explains how to work with N:M (many-to-many) relationships between production methods and tests.

## What are N:M Relationships?

N:M means multiple production methods linked to multiple tests:

```
Production Methods (N)     Tests (M)
├── UserService::create    ├── test_creates_user
├── UserService::validate  ├── test_validates_email
└── UserService::save      └── test_saves_to_database
```

When these all use the same placeholder or explicit links, you get N × M links.

## Common Scenarios

### 1. Integration test covering multiple methods

One test verifies a workflow that uses multiple methods:

```php
// Production
class OrderService
{
    #[TestedBy('Tests\OrderFlowTest', 'test_complete_order_flow')]
    public function validate(Order $order): bool { }

    #[TestedBy('Tests\OrderFlowTest', 'test_complete_order_flow')]
    public function process(Order $order): void { }

    #[TestedBy('Tests\OrderFlowTest', 'test_complete_order_flow')]
    public function notify(Order $order): void { }
}

// Test (1 test → 3 methods)
test('complete order flow', function () {
    $service = new OrderService();
    $order = new Order();

    expect($service->validate($order))->toBeTrue();
    $service->process($order);
    $service->notify($order);
})
->links(OrderService::class.'::validate')
->links(OrderService::class.'::process')
->links(OrderService::class.'::notify');
```

### 2. Multiple tests for one method

Multiple tests verify different aspects of one method:

```php
// Production (1 method → 3 tests)
class Calculator
{
    #[TestedBy('Tests\CalculatorTest', 'test_adds_positive_numbers')]
    #[TestedBy('Tests\CalculatorTest', 'test_adds_negative_numbers')]
    #[TestedBy('Tests\CalculatorTest', 'test_adds_zeros')]
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}

// Tests
test('adds positive numbers', function () {
    expect((new Calculator())->add(2, 3))->toBe(5);
})->linksAndCovers(Calculator::class.'::add');

test('adds negative numbers', function () {
    expect((new Calculator())->add(-2, -3))->toBe(-5);
})->linksAndCovers(Calculator::class.'::add');

test('adds zeros', function () {
    expect((new Calculator())->add(0, 0))->toBe(0);
})->linksAndCovers(Calculator::class.'::add');
```

### 3. True N:M with placeholders

Multiple methods and multiple tests share a placeholder:

```php
// Production (N=2 methods)
class PaymentService
{
    #[TestedBy('@payment-flow')]
    public function charge(int $amount): bool { }

    #[TestedBy('@payment-flow')]
    public function refund(int $amount): bool { }
}

// Tests (M=3 tests)
test('charges valid amount')
    ->linksAndCovers('@payment-flow');

test('refunds charged payment')
    ->linksAndCovers('@payment-flow');

test('handles payment errors')
    ->linksAndCovers('@payment-flow');
```

After `testlink pair`, this creates 2 × 3 = 6 links.

## Managing N:M Relationships

### Viewing N:M in reports

```bash
./vendor/bin/testlink report
```

```
Coverage Links Report
─────────────────────

App\PaymentService

  charge()
    → Tests\PaymentServiceTest::charges valid amount
    → Tests\PaymentServiceTest::refunds charged payment
    → Tests\PaymentServiceTest::handles payment errors

  refund()
    → Tests\PaymentServiceTest::charges valid amount
    → Tests\PaymentServiceTest::refunds charged payment
    → Tests\PaymentServiceTest::handles payment errors
```

### Avoiding unintended N:M

Use specific placeholders to avoid explosion:

```php
// Instead of one placeholder for everything
#[TestedBy('@payment')]  // Too broad

// Use specific placeholders
#[TestedBy('@payment-charge')]
#[TestedBy('@payment-refund')]
```

### Splitting large N:M

If N:M gets too large, split by concern:

```php
// Before: 5 methods × 10 tests = 50 links
#[TestedBy('@user-feature')]

// After: More targeted
#[TestedBy('@user-create')]      // 1 method × 3 tests = 3 links
#[TestedBy('@user-update')]      // 2 methods × 4 tests = 8 links
#[TestedBy('@user-delete')]      // 2 methods × 3 tests = 6 links
```

## Placeholder N:M Resolution

### Preview N:M expansion

```bash
./vendor/bin/testlink pair --dry-run
```

Output shows the multiplication:

```
Found Placeholders:
  @payment-flow   2 production × 3 tests = 6 links
  @user-create    1 production × 2 tests = 2 links
  @order-process  3 production × 4 tests = 12 links

Total: 20 links will be created
```

### Resolve step by step

For large N:M, resolve one placeholder at a time:

```bash
# Preview one
./vendor/bin/testlink pair --dry-run --placeholder=@payment-flow

# Resolve one
./vendor/bin/testlink pair --placeholder=@payment-flow

# Review result before next
./vendor/bin/testlink validate
```

## Best Practices for N:M

### 1. Use descriptive placeholders

```php
// Good - clear scope
#[TestedBy('@checkout-validation')]
#[TestedBy('@checkout-payment')]

// Avoid - too broad
#[TestedBy('@checkout')]
```

### 2. Match granularity

| Test Type | Placeholder Scope |
|-----------|-------------------|
| Unit test | Single method |
| Integration test | Feature/workflow |
| E2E test | User story |

### 3. Document complex N:M

Add comments explaining the relationship:

```php
/**
 * Payment processing - validates and charges.
 *
 * Linked tests cover both validation and charging scenarios.
 * See @payment-charge for specific tests.
 */
#[TestedBy('@payment-charge')]
#[TestedBy('@payment-charge')]
public function processPayment(): void
```

### 4. Review before resolving

Always preview large N:M resolutions:

```bash
./vendor/bin/testlink pair --dry-run
```

If you see something like "5 × 20 = 100 links", reconsider your placeholder strategy.

## Troubleshooting N:M

### Too many links after resolve

If resolution created too many links:

1. Revert with git
2. Split the placeholder into smaller, more specific ones
3. Resolve again

### Missing links in N:M

If some links are missing:

1. Check that both sides use the same placeholder spelling
2. Verify both files are in the scanned paths
3. Run with `--verbose` for details

```bash
./vendor/bin/testlink pair --dry-run --verbose
```
