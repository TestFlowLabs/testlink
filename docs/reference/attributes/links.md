# #[Links]

Declares that a test links to production methods without affecting code coverage.

## Signature

```php
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Links
{
    public function __construct(
        public string $class,
        public ?string $method = null
    ) {}
}
```

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$class` | string | Yes | Production class name or placeholder |
| `$method` | string\|null | No | Method name (null for class-level) |

## Usage

### Basic usage

```php
use TestFlowLabs\TestingAttributes\Links;

class UserFlowTest extends TestCase
{
    #[Links(UserService::class, 'create')]
    public function test_complete_registration_flow(): void
    {
        // Integration test that exercises create() but
        // doesn't need to count toward its coverage
    }
}
```

### Multiple links

```php
#[Links(UserService::class, 'create')]
#[Links(EmailService::class, 'sendWelcome')]
#[Links(NotificationService::class, 'notify')]
public function test_complete_registration_flow(): void
{
    // Links to multiple methods without coverage
}
```

### With placeholder

```php
#[Links('@user-flow')]
public function test_registration_flow(): void
{
    // Placeholder resolved with `testlink pair`
}
```

## When to Use #[Links]

### Integration tests

Tests that exercise code but shouldn't claim coverage:

```php
#[Links(CheckoutService::class, 'process')]
public function test_complete_checkout_flow(): void
{
    // The unit tests provide coverage
    // This integration test just verifies the flow
}
```

### E2E tests

End-to-end tests that test the whole system:

```php
#[Links(UserService::class, 'create')]
#[Links(OrderService::class, 'create')]
#[Links(PaymentService::class, 'process')]
public function test_user_can_purchase_item(): void
{
    // E2E test - coverage handled by unit tests
}
```

### When unit tests provide coverage

```php
// Unit test - provides coverage
#[LinksAndCovers(Calculator::class, 'add')]
public function test_adds_two_numbers(): void
{
    $calc = new Calculator();
    $this->assertSame(5, $calc->add(2, 3));
}

// Integration test - just links
#[Links(Calculator::class, 'add')]
public function test_calculator_in_report(): void
{
    // Tests Calculator in context but doesn't need
    // to double-count coverage
}
```

## vs #[LinksAndCovers]

| Attribute | Creates Link | Includes Coverage |
|-----------|-------------|-------------------|
| `#[Links]` | ✓ | ✗ |
| `#[LinksAndCovers]` | ✓ | ✓ |

### Coverage implications

Using `#[LinksAndCovers]` on multiple tests may inflate coverage:

```php
// Both tests cover the same method
#[LinksAndCovers(UserService::class, 'create')]  // Counts toward coverage
public function test_creates_user(): void

#[LinksAndCovers(UserService::class, 'create')]  // Also counts (double counting)
public function test_creates_admin_user(): void
```

Using `#[Links]` for secondary tests:

```php
// Primary test provides coverage
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void

// Secondary test just links
#[Links(UserService::class, 'create')]
public function test_creates_admin_user(): void
```

## Pest Equivalent

`#[Links]` is the PHPUnit equivalent of Pest's `->links()`:

```php
// PHPUnit
#[Links(UserService::class, 'create')]
public function test_registration_flow(): void { }

// Pest equivalent
test('registration flow', function () { })
    ->links(UserService::class.'::create');
```

## Validation

TestLink validates `#[Links]` attributes:

```bash
./vendor/bin/testlink validate
```

### Checks

- Class exists
- Method exists (if specified)

### Note

`#[Links]` does not require a corresponding `#[TestedBy]` in production code (unlike `#[LinksAndCovers]`).

## Sync Behavior

When running `testlink sync --link-only`:

1. Links attributes are read
2. Corresponding production files are found
3. @see tags are added to production methods

The sync behavior is the same as `#[LinksAndCovers]`.

## Best Practices

### Use for integration/E2E tests

```php
// Unit test - use LinksAndCovers
#[LinksAndCovers(OrderService::class, 'create')]
public function test_creates_order(): void

// Integration test - use Links
#[Links(OrderService::class, 'create')]
public function test_order_in_checkout_flow(): void
```

### Document the relationship

```php
/**
 * Integration test for user registration flow.
 * Unit test coverage provided by UserServiceTest.
 */
#[Links(UserService::class, 'create')]
#[Links(UserService::class, 'sendVerification')]
public function test_user_registration_flow(): void
```

### Combine with LinksAndCovers when appropriate

```php
#[LinksAndCovers(PaymentService::class, 'processRefund')]  // This method needs coverage
#[Links(PaymentService::class, 'process')]                  // Exercised but covered elsewhere
public function test_refund_after_payment(): void
{
    // Test focuses on refund, process is just setup
}
```

