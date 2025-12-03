# Best Practices Overview

Follow these guidelines to get the most out of TestLink in your projects.

## Core Principles

### 1. Explicit Coverage Links

Add coverage links to your tests to document which production code they verify:

::: code-group

```php [Pest]
// Good - Clear coverage links
test('places order successfully', function () {
    // ...
})->linksAndCovers(OrderService::class.'::placeOrder');

test('validates stock before placing', function () {
    // ...
})->linksAndCovers(OrderService::class.'::placeOrder');
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\LinksAndCovers;

// Good - Clear coverage links
class OrderServiceTest extends TestCase
{
    #[LinksAndCovers(OrderService::class, 'placeOrder')]
    public function test_places_order_successfully(): void
    {
        // ...
    }

    #[LinksAndCovers(OrderService::class, 'placeOrder')]
    public function test_validates_stock_before_placing(): void
    {
        // ...
    }
}
```

:::

### 2. One Test, One Purpose

Each test should verify one specific behavior:

::: code-group

```php [Pest]
// Good - Focused tests
test('creates user with valid data', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');

test('fails when email is invalid', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');

// Avoid - Test doing too much
test('creates user and sends email and logs activity', function () {
    // Multiple behaviors in one test
});
```

```php [PHPUnit]
// Good - Focused tests
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user_with_valid_data(): void
{
    // ...
}

#[LinksAndCovers(UserService::class, 'create')]
public function test_fails_when_email_is_invalid(): void
{
    // ...
}

// Avoid - Test doing too much
public function test_creates_user_and_sends_email_and_logs_activity(): void
{
    // Multiple behaviors in one test
}
```

:::

### 3. Meaningful Test Names

Test names should describe the behavior being tested:

::: code-group

```php [Pest]
// Good - Descriptive names
test('calculates total with discount applied', function () { })
    ->linksAndCovers(Cart::class.'::calculateTotal');

test('throws exception when cart is empty', function () { })
    ->linksAndCovers(Cart::class.'::calculateTotal');

// Avoid - Vague names
test('test1', function () { });
test('it works', function () { });
```

```php [PHPUnit]
// Good - Descriptive names
#[LinksAndCovers(Cart::class, 'calculateTotal')]
public function test_calculates_total_with_discount_applied(): void { }

#[LinksAndCovers(Cart::class, 'calculateTotal')]
public function test_throws_exception_when_cart_is_empty(): void { }

// Avoid - Vague names
public function test1(): void { }
public function testItWorks(): void { }
```

:::

### 4. Validate Links Regularly

Run validation to catch issues early:

```bash
# In development
testlink validate

# In CI
testlink validate && vendor/bin/pest  # or phpunit
```

## Quick Reference

| Practice | Do | Don't |
|----------|-----|-------|
| Link placement | Test methods | No links |
| Test naming | Describe behavior | Generic names |
| Validation | Run in CI | Skip validation |
| Orphan cleanup | Use `--prune` | Leave stale links |

## Sections

- [Naming Conventions](/best-practices/naming-conventions) - How to name tests and links
- [Test Organization](/best-practices/test-organization) - Structuring tests with coverage links
- [CI Integration](/best-practices/ci-integration) - Automated validation in pipelines
