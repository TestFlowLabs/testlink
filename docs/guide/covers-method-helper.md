# Linking from Tests

TestLink provides two types of links from tests to production code:

- **linksAndCovers** - Traceability + coverage tracking
- **links** - Traceability only (no coverage)

Both work with Pest and PHPUnit, using different syntax for each framework.

## Link Types

| Type | Pest Method | PHPUnit Attribute | Purpose |
|------|-------------|-------------------|---------|
| Link + Coverage | `->linksAndCovers()` | `#[LinksAndCovers]` | Traceability + triggers coverage |
| Link Only | `->links()` | `#[Links]` | Traceability without coverage |

## Pest Setup

Add the RuntimeBootstrap to your `tests/Pest.php`:

```php
// tests/Pest.php
use TestFlowLabs\TestLink\Runtime\RuntimeBootstrap;

RuntimeBootstrap::init();
```

## Basic Usage

::: code-group

```php [Pest]
test('creates a new user', function () {
    $user = app(UserService::class)->create([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    expect($user)->toBeInstanceOf(User::class);
})->linksAndCovers(UserService::class.'::create');
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_a_new_user(): void
    {
        $user = app(UserService::class)->create([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
    }
}
```

:::

## Link Only (No Coverage)

Use for integration or e2e tests where unit coverage is tracked elsewhere:

::: code-group

```php [Pest]
test('creates user through API endpoint', function () {
    $response = $this->post('/api/users', [
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    $response->assertCreated();
})->links(UserService::class.'::create');
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\Links;

class UserApiTest extends TestCase
{
    #[Links(UserService::class, 'create')]
    public function test_creates_user_through_api(): void
    {
        $response = $this->post('/api/users', [
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $response->assertCreated();
    }
}
```

:::

## Method Reference Formats

### Pest

| Format | Example |
|--------|---------|
| Specific method | `UserService::class.'::create'` |
| Entire class | `UserService::class` |

### PHPUnit

| Format | Example |
|--------|---------|
| Specific method | `#[LinksAndCovers(UserService::class, 'create')]` |
| Entire class | `#[LinksAndCovers(UserService::class)]` |

## Multiple Methods

::: code-group

```php [Pest]
test('complete checkout flow', function () {
    // Test that exercises multiple methods
})->linksAndCovers(CartService::class.'::checkout')
  ->linksAndCovers(PaymentService::class.'::charge')
  ->linksAndCovers(OrderService::class.'::create')
  ->links(EmailService::class.'::sendConfirmation');
```

```php [PHPUnit]
#[LinksAndCovers(CartService::class, 'checkout')]
#[LinksAndCovers(PaymentService::class, 'charge')]
#[LinksAndCovers(OrderService::class, 'create')]
#[Links(EmailService::class, 'sendConfirmation')]
public function test_complete_checkout_flow(): void
{
    // Test that exercises multiple methods
}
```

:::

## With Other Pest Methods

Link methods work with all Pest chaining methods:

```php
test('premium user gets discount', function () {
    // ...
})->linksAndCovers(DiscountService::class.'::calculate')
  ->group('pricing')
  ->skip(fn () => !config('features.discounts'));
```

```php
it('sends welcome email', function () {
    // ...
})->linksAndCovers(EmailService::class.'::sendWelcome')
  ->throws(MailException::class);
```

## Inside Describe Blocks (Pest)

```php
describe('UserService', function () {
    describe('create method', function () {
        it('creates with valid data', function () {
            // ...
        })->linksAndCovers(UserService::class.'::create');

        it('validates email format', function () {
            // ...
        })->linksAndCovers(UserService::class.'::create');
    });
});
```

## When to Use Each

| Scenario | Method | Reason |
|----------|--------|--------|
| Unit test | `linksAndCovers` / `#[LinksAndCovers]` | Want coverage tracking |
| Integration test | `links` / `#[Links]` | Coverage tracked in unit tests |
| E2E test | `links` / `#[Links]` | Coverage tracked in unit tests |
| Feature test | `linksAndCovers` / `#[LinksAndCovers]` | Primary coverage point |

## Validation

Check your links are valid:

```bash
testlink validate
```

## View All Links

Generate a report of all coverage links:

```bash
testlink report
```
