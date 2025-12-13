# Pest Methods

TestLink provides method chains for Pest tests to declare coverage links.

## Available Methods

| Method | Description |
|--------|-------------|
| `linksAndCovers()` | Links to production code (with coverage) |
| `links()` | Links to production code (without coverage) |

## linksAndCovers()

Declares that a test covers and links to production methods.

### Signature

```php
->linksAndCovers(string $methodIdentifier): self
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$methodIdentifier` | string | `Class::method` format or placeholder |

### Usage

```php
test('creates user', function () {
    $service = new UserService();
    $user = $service->create(['name' => 'John']);

    expect($user)->toBeInstanceOf(User::class);
})->linksAndCovers(UserService::class.'::create');
```

### Multiple methods

```php
test('creates validated user', function () {
    // ...
})
->linksAndCovers(UserService::class.'::create')
->linksAndCovers(UserValidator::class.'::validate');
```

### With describe blocks

```php
describe('UserService', function () {
    describe('create', function () {
        test('creates user with valid data', function () {
            // ...
        })->linksAndCovers(UserService::class.'::create');

        test('validates email format', function () {
            // ...
        })->linksAndCovers(UserService::class.'::create');
    });
});
```

### With placeholder

```php
test('creates user', function () {
    // ...
})->linksAndCovers('@user-create');
```

## links()

Declares that a test links to production methods without affecting coverage.

### Signature

```php
->links(string $methodIdentifier): self
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$methodIdentifier` | string | `Class::method` format or placeholder |

### Usage

```php
test('complete registration flow', function () {
    // Integration test
})
->links(UserService::class.'::create')
->links(EmailService::class.'::sendWelcome');
```

### When to use

Use `links()` for:
- Integration tests
- E2E tests
- Tests where unit tests provide coverage

```php
// Unit test - provides coverage
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');

// Integration test - just links
test('user registration flow', function () {
    // ...
})->links(UserService::class.'::create');
```

## Method Identifier Format

### Using ::class constant

```php
// Recommended format
->linksAndCovers(UserService::class.'::create')
```

This becomes `"App\Services\UserService::create"`.

### String format

```php
// Also valid
->linksAndCovers('App\Services\UserService::create')
```

### Placeholder format

```php
// Resolved with `testlink pair`
->linksAndCovers('@user-create')
```

## Chaining

Methods can be chained in any order:

```php
test('creates and validates user', function () {
    // ...
})
->linksAndCovers(UserService::class.'::create')
->linksAndCovers(UserValidator::class.'::validate')
->links(AuditService::class.'::log');
```

## Comparison with PHPUnit Attributes

| Pest | PHPUnit |
|------|---------|
| `->linksAndCovers(Class::class.'::method')` | `#[LinksAndCovers(Class::class, 'method')]` |
| `->links(Class::class.'::method')` | `#[Links(Class::class, 'method')]` |

### Converting between formats

```php
// Pest
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');

// PHPUnit equivalent
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
{
    // ...
}
```

## Common Patterns

### One test, one method

```php
test('adds two numbers', function () {
    expect((new Calculator())->add(2, 3))->toBe(5);
})->linksAndCovers(Calculator::class.'::add');
```

### One test, multiple methods

```php
test('processes order', function () {
    // Tests complete order processing
})
->linksAndCovers(OrderService::class.'::validate')
->linksAndCovers(OrderService::class.'::save')
->linksAndCovers(OrderService::class.'::notify');
```

### Multiple tests, one method

```php
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

### Mixed links and linksAndCovers

```php
test('refund after payment', function () {
    // Focus is on refund, payment is just setup
})
->linksAndCovers(PaymentService::class.'::refund')  // Coverage for refund
->links(PaymentService::class.'::charge');          // No coverage for charge
```

## Validation

TestLink validates Pest method chains:

```bash
./vendor/bin/testlink validate
```

### Errors

```
✗ Invalid linksAndCovers:
  tests/UserServiceTest.php:15
    → App\UserService::nonexistent (method not found)
```

