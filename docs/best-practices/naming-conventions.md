# Naming Conventions

Consistent naming makes coverage links easier to maintain and understand.

## Test Names

### Use Descriptive Behavior Names

Test names should describe **what** is being tested and **what** the expected outcome is:

```php
// Good - Clear behavior description
#[TestedBy(UserServiceTest::class, 'creates user with valid data')]
#[TestedBy(UserServiceTest::class, 'throws exception when email is duplicate')]
#[TestedBy(UserServiceTest::class, 'hashes password before storing')]

// Avoid - Vague or technical names
#[TestedBy(UserServiceTest::class, 'test create')]
#[TestedBy(UserServiceTest::class, 'testUserCreation')]
#[TestedBy(UserServiceTest::class, 'it works')]
```

### Framework-Specific Patterns

::: code-group

```php [Pest Style]
// Pest uses lowercase, sentence-style test names
test('creates user with valid data', function () { });

// Maps to
#[TestedBy(UserServiceTest::class, 'creates user with valid data')]
```

```php [PHPUnit Style]
// PHPUnit uses method names with test_ prefix
public function test_creates_user_with_valid_data(): void { }

// Maps to
#[TestedBy(UserServiceTest::class, 'test_creates_user_with_valid_data')]
```

:::

### Common Patterns

| Pattern | Example |
|---------|---------|
| Action + condition | `creates user with valid data` |
| Throws + condition | `throws exception when cart is empty` |
| Returns + condition | `returns null when user not found` |
| Validates + field | `validates email format` |
| Fails + condition | `fails when password is too short` |

## Test Class Names

### Mirror Production Structure

```
src/
├── Services/
│   ├── UserService.php        → tests/Unit/Services/UserServiceTest.php
│   └── OrderService.php       → tests/Unit/Services/OrderServiceTest.php
├── Models/
│   └── User.php               → tests/Unit/Models/UserTest.php
└── Http/Controllers/
    └── UserController.php     → tests/Feature/Http/Controllers/UserControllerTest.php
```

### Use Consistent Suffixes

```php
// Production class
class UserService { }

// Test class (add Test suffix)
class UserServiceTest { }
```

## Method Coverage Naming

### Use Full Class Path

::: code-group

```php [Pest]
// Good - Uses ::class for IDE support
->linksAndCovers(UserService::class.'::create')

// Avoid - String-only (no IDE support)
->linksAndCovers('App\Services\UserService::create')
```

```php [PHPUnit]
// Good - Uses ::class for IDE support
#[LinksAndCovers(UserService::class, 'create')]

// Avoid - String-only (no IDE support)
#[LinksAndCovers('App\Services\UserService', 'create')]
```

:::

### Multiple Methods

When a test covers multiple methods:

::: code-group

```php [Pest]
test('checkout flow completes', function () {
    // ...
})->linksAndCovers(CartService::class.'::checkout')
  ->linksAndCovers(PaymentService::class.'::charge')
  ->linksAndCovers(OrderService::class.'::create');
```

```php [PHPUnit]
#[LinksAndCovers(CartService::class, 'checkout')]
#[LinksAndCovers(PaymentService::class, 'charge')]
#[LinksAndCovers(OrderService::class, 'create')]
public function test_checkout_flow_completes(): void
{
    // ...
}
```

:::

## Attribute Grouping

### Group by Behavior

```php
class OrderService
{
    // Happy path tests
    #[TestedBy(OrderServiceTest::class, 'places order successfully')]
    #[TestedBy(OrderServiceTest::class, 'sends confirmation email')]

    // Validation tests
    #[TestedBy(OrderServiceTest::class, 'validates stock availability')]
    #[TestedBy(OrderServiceTest::class, 'validates shipping address')]

    // Edge cases
    #[TestedBy(OrderServiceTest::class, 'handles concurrent orders')]
    public function placeOrder(Order $order): void
    {
        // ...
    }
}
```

### Keep Attributes Close to Method

```php
// Good - Attributes directly above method
#[TestedBy(UserTest::class, 'creates user')]
public function create(): User
{
    // ...
}

// Avoid - Attributes far from method
#[TestedBy(UserTest::class, 'creates user')]
// Many lines of comments or other code
// ...
public function create(): User
{
    // ...
}
```

## Examples

### Service Class

```php
namespace App\Services;

use App\Tests\Unit\Services\PaymentServiceTest;
use TestFlowLabs\TestingAttributes\TestedBy;

class PaymentService
{
    #[TestedBy(PaymentServiceTest::class, 'charges card successfully')]
    #[TestedBy(PaymentServiceTest::class, 'throws exception on invalid card')]
    #[TestedBy(PaymentServiceTest::class, 'retries on temporary failure')]
    public function charge(Card $card, Money $amount): Transaction
    {
        // ...
    }

    #[TestedBy(PaymentServiceTest::class, 'refunds full amount')]
    #[TestedBy(PaymentServiceTest::class, 'refunds partial amount')]
    public function refund(Transaction $transaction, ?Money $amount = null): Refund
    {
        // ...
    }
}
```

### Corresponding Test Files

::: code-group

```php [Pest]
// tests/Unit/Services/PaymentServiceTest.php

test('charges card successfully', function () {
    $service = new PaymentService();
    $transaction = $service->charge($validCard, Money::USD(1000));

    expect($transaction)->toBeInstanceOf(Transaction::class);
})->linksAndCovers(PaymentService::class.'::charge');

test('throws exception on invalid card', function () {
    $service = new PaymentService();

    expect(fn() => $service->charge($invalidCard, Money::USD(1000)))
        ->toThrow(InvalidCardException::class);
})->linksAndCovers(PaymentService::class.'::charge');

test('refunds full amount', function () {
    $service = new PaymentService();
    $refund = $service->refund($transaction);

    expect($refund->amount)->toBe($transaction->amount);
})->linksAndCovers(PaymentService::class.'::refund');
```

```php [PHPUnit]
// tests/Unit/Services/PaymentServiceTest.php

namespace Tests\Unit\Services;

use App\Services\PaymentService;
use TestFlowLabs\TestingAttributes\LinksAndCovers;
use PHPUnit\Framework\TestCase;

class PaymentServiceTest extends TestCase
{
    #[LinksAndCovers(PaymentService::class, 'charge')]
    public function test_charges_card_successfully(): void
    {
        $service = new PaymentService();
        $transaction = $service->charge($this->validCard, Money::USD(1000));

        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    #[LinksAndCovers(PaymentService::class, 'charge')]
    public function test_throws_exception_on_invalid_card(): void
    {
        $this->expectException(InvalidCardException::class);

        $service = new PaymentService();
        $service->charge($this->invalidCard, Money::USD(1000));
    }

    #[LinksAndCovers(PaymentService::class, 'refund')]
    public function test_refunds_full_amount(): void
    {
        $service = new PaymentService();
        $refund = $service->refund($this->transaction);

        $this->assertEquals($this->transaction->amount, $refund->amount);
    }
}
```

:::
