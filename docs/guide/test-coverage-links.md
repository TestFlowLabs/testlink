# Test Coverage Links

Test coverage links create explicit connections between your tests and the production code they verify. Unlike code coverage metrics that measure line execution, coverage links document the **intent** of your tests.

## Why Use Coverage Links?

### 1. Documentation

Coverage links serve as living documentation:

::: code-group

```php [Pest]
test('creates order with valid data', function () {
    // Looking at this test, you immediately know:
    // - Which production method it covers
    // - What behavior is being tested
})->linksAndCovers(OrderService::class.'::create');

test('applies discount codes', function () {
    // ...
})->linksAndCovers(OrderService::class.'::create');

test('calculates shipping', function () {
    // ...
})->linksAndCovers(OrderService::class.'::create');
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class OrderServiceTest extends TestCase
{
    #[LinksAndCovers(OrderService::class, 'create')]
    public function test_creates_order_with_valid_data(): void
    {
        // Looking at this test, you immediately know:
        // - Which production method it covers
        // - What behavior is being tested
    }

    #[LinksAndCovers(OrderService::class, 'create')]
    public function test_applies_discount_codes(): void
    {
        // ...
    }

    #[LinksAndCovers(OrderService::class, 'create')]
    public function test_calculates_shipping(): void
    {
        // ...
    }
}
```

:::

### 2. Refactoring Safety

When renaming or moving methods, coverage links help you find affected tests:

```bash
# Find all tests that cover a specific method
testlink report | grep "OrderService::create"
```

### 3. Test Discovery

Find tests for any method instantly:

```bash
testlink report
```

### 4. Gap Detection

Run the report to identify methods that might need more test coverage.

## Linking Model

Coverage links are defined in test files, pointing to the production code they verify:

```
Test Code                              Production Code
┌─────────────────────────────┐        ┌─────────────────┐
│ linksAndCovers() (Pest)     │ ─────► │ UserService     │
│ #[LinksAndCovers] (PHPUnit) │        │ ::create()      │
└─────────────────────────────┘        └─────────────────┘
```

::: code-group

```php [Pest]
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_user(): void
    {
        // ...
    }
}
```

:::

## Multiple Links

A single test can cover multiple methods:

::: code-group

```php [Pest]
test('complete checkout flow', function () {
    // ...
})->linksAndCovers(CartService::class.'::checkout')
  ->linksAndCovers(PaymentService::class.'::charge')
  ->linksAndCovers(OrderService::class.'::create');
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class CheckoutTest extends TestCase
{
    #[LinksAndCovers(CartService::class, 'checkout')]
    #[LinksAndCovers(PaymentService::class, 'charge')]
    #[LinksAndCovers(OrderService::class, 'create')]
    public function test_complete_checkout_flow(): void
    {
        // ...
    }
}
```

:::

## Class-Level Coverage

You can also link at the class level (without specifying a method):

::: code-group

```php [Pest]
test('repository operations', function () {
    // ...
})->linksAndCovers(UserRepository::class);
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\LinksAndCovers;

#[LinksAndCovers(UserRepository::class)]
class UserRepositoryTest extends TestCase
{
    public function test_repository_operations(): void
    {
        // ...
    }
}
```

:::

## Validation

Run validation to ensure your links are valid:

```bash
testlink validate
```
