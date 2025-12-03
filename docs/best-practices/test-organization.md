# Test Organization

Organize your tests to maximize the benefits of coverage links.

## Directory Structure

### Mirror Production Code

```
app/                                    tests/
├── Services/                           ├── Unit/
│   ├── UserService.php                 │   └── Services/
│   ├── OrderService.php                │       ├── UserServiceTest.php
│   └── PaymentService.php              │       ├── OrderServiceTest.php
├── Models/                             │       └── PaymentServiceTest.php
│   ├── User.php                        │   └── Models/
│   └── Order.php                       │       ├── UserTest.php
└── Http/                               │       └── OrderTest.php
    └── Controllers/                    └── Feature/
        └── UserController.php              └── Http/
                                                └── Controllers/
                                                    └── UserControllerTest.php
```

### Benefits

- Easy to find tests for any production class
- Natural mapping for `#[TestedBy]` attributes
- Clear separation of unit and feature tests

## Test File Organization

### Group Tests by Method

::: code-group

```php [Pest]
// tests/Unit/Services/UserServiceTest.php

describe('create', function () {
    test('creates user with valid data', function () {
        // ...
    })->linksAndCovers(UserService::class.'::create');

    test('hashes password before storing', function () {
        // ...
    })->linksAndCovers(UserService::class.'::create');

    test('throws exception when email exists', function () {
        // ...
    })->linksAndCovers(UserService::class.'::create');
});

describe('update', function () {
    test('updates user data', function () {
        // ...
    })->linksAndCovers(UserService::class.'::update');

    test('validates email format', function () {
        // ...
    })->linksAndCovers(UserService::class.'::update');
});
```

```php [PHPUnit]
// tests/Unit/Services/UserServiceTest.php

class UserServiceTest extends TestCase
{
    // Create method tests
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_user_with_valid_data(): void
    {
        // ...
    }

    #[LinksAndCovers(UserService::class, 'create')]
    public function test_hashes_password_before_storing(): void
    {
        // ...
    }

    #[LinksAndCovers(UserService::class, 'create')]
    public function test_throws_exception_when_email_exists(): void
    {
        // ...
    }

    // Update method tests
    #[LinksAndCovers(UserService::class, 'update')]
    public function test_updates_user_data(): void
    {
        // ...
    }

    #[LinksAndCovers(UserService::class, 'update')]
    public function test_validates_email_format(): void
    {
        // ...
    }
}
```

:::

### Group Tests by Scenario (Pest)

```php
describe('user registration flow', function () {
    test('registers new user', function () {
        // ...
    })->linksAndCovers(UserService::class.'::create');

    test('sends welcome email', function () {
        // ...
    })->linksAndCovers(UserService::class.'::create')
      ->linksAndCovers(EmailService::class.'::sendWelcome');

    test('logs registration event', function () {
        // ...
    })->linksAndCovers(UserService::class.'::create')
      ->linksAndCovers(AuditService::class.'::log');
});
```

## When to Create Separate Test Files

### Single Test File Per Class (Default)

```php
// UserService.php has create(), update(), delete()
// All tests in UserServiceTest.php
```

### Split by Complexity

For complex classes, consider splitting:

```
tests/Unit/Services/User/
├── UserServiceCreateTest.php
├── UserServiceUpdateTest.php
└── UserServiceDeleteTest.php
```

Update `#[TestedBy]` attributes accordingly:

```php
class UserService
{
    #[TestedBy(UserServiceCreateTest::class, 'creates user')]
    public function create() { }

    #[TestedBy(UserServiceUpdateTest::class, 'updates user')]
    public function update() { }
}
```

## Coverage Link Placement

### Inside describe() Blocks (Pest)

```php
describe('UserService', function () {
    test('creates user', function () {
        // ...
    })->linksAndCovers(UserService::class.'::create');
});
```

### With beforeEach() (Pest)

```php
describe('UserService', function () {
    beforeEach(function () {
        $this->service = new UserService();
    });

    test('creates user', function () {
        $user = $this->service->create(['name' => 'John']);
        expect($user)->toBeInstanceOf(User::class);
    })->linksAndCovers(UserService::class.'::create');
});
```

### With setUp() (PHPUnit)

```php
class UserServiceTest extends TestCase
{
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService();
    }

    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_user(): void
    {
        $user = $this->service->create(['name' => 'John']);
        $this->assertInstanceOf(User::class, $user);
    }
}
```

## Integration Tests

### Feature Tests with Multiple Coverage Links

::: code-group

```php [Pest]
// tests/Feature/CheckoutTest.php

test('complete checkout flow', function () {
    $cart = createCart();
    $user = createUser();

    // Test the full flow
    $order = checkout($cart, $user);

    expect($order)->toBeInstanceOf(Order::class);
})->linksAndCovers(CartService::class.'::checkout')
  ->linksAndCovers(PaymentService::class.'::charge')
  ->linksAndCovers(OrderService::class.'::create')
  ->linksAndCovers(EmailService::class.'::sendConfirmation');
```

```php [PHPUnit]
// tests/Feature/CheckoutTest.php

#[LinksAndCovers(CartService::class, 'checkout')]
#[LinksAndCovers(PaymentService::class, 'charge')]
#[LinksAndCovers(OrderService::class, 'create')]
#[LinksAndCovers(EmailService::class, 'sendConfirmation')]
public function test_complete_checkout_flow(): void
{
    $cart = $this->createCart();
    $user = $this->createUser();

    $order = $this->checkout($cart, $user);

    $this->assertInstanceOf(Order::class, $order);
}
```

:::

### HTTP Tests

::: code-group

```php [Pest]
// tests/Feature/Http/Controllers/UserControllerTest.php

test('POST /users creates new user', function () {
    $response = $this->postJson('/users', [
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    $response->assertStatus(201);
})->linksAndCovers(UserController::class.'::store')
  ->linksAndCovers(UserService::class.'::create');
```

```php [PHPUnit]
// tests/Feature/Http/Controllers/UserControllerTest.php

#[LinksAndCovers(UserController::class, 'store')]
#[LinksAndCovers(UserService::class, 'create')]
public function test_post_users_creates_new_user(): void
{
    $response = $this->postJson('/users', [
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    $response->assertStatus(201);
}
```

:::

## Organizing by Test Type

```
tests/
├── Unit/                    # Isolated unit tests
│   └── Services/
│       └── UserServiceTest.php
├── Feature/                 # Integration/HTTP tests
│   └── Http/
│       └── UserControllerTest.php
├── Browser/                 # End-to-end tests (Dusk)
│   └── UserRegistrationTest.php
└── Pest.php                 # Shared helpers and config (Pest only)
```

Each directory can use coverage links:

::: code-group

```php [Unit Test - Pest]
test('hashes password', function () { })
    ->linksAndCovers(UserService::class.'::hashPassword');
```

```php [Unit Test - PHPUnit]
#[LinksAndCovers(UserService::class, 'hashPassword')]
public function test_hashes_password(): void { }
```

```php [Feature Test - Pest]
test('registration form works', function () { })
    ->linksAndCovers(UserController::class.'::store')
    ->linksAndCovers(UserService::class.'::create');
```

```php [Feature Test - PHPUnit]
#[LinksAndCovers(UserController::class, 'store')]
#[LinksAndCovers(UserService::class, 'create')]
public function test_registration_form_works(): void { }
```

:::
