# Links vs LinksAndCovers

Understanding when to use `#[Links]` versus `#[LinksAndCovers]` for optimal test organization.

## Quick Comparison

| Attribute | Creates Link | Counts as Coverage |
|-----------|-------------|-------------------|
| `#[LinksAndCovers]` / `->linksAndCovers()` | ✓ | ✓ |
| `#[Links]` / `->links()` | ✓ | ✗ |

Both create bidirectional links. The difference is whether the test **claims coverage** of the production method.

## What is "Coverage"?

In TestLink, "coverage" means the test is the **primary verification** of that production method:

:::tabs key:stack
== Pest

```php
// This test IS the coverage for UserService::create
test('creates user with valid data', function () {
    $service = new UserService();
    $user = $service->create(['name' => 'John', 'email' => 'john@example.com']);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('John');
})->linksAndCovers(UserService::class.'::create');
```

== PHPUnit + Attributes

```php
// This test IS the coverage for UserService::create
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user_with_valid_data(): void
{
    $service = new UserService();
    $user = $service->create(['name' => 'John', 'email' => 'john@example.com']);

    $this->assertInstanceOf(User::class, $user);
    $this->assertSame('John', $user->name);
}
```

== PHPUnit + @see

```php
/**
 * This test IS the coverage for UserService::create
 * @see \App\Services\UserService::create
 */
public function test_creates_user_with_valid_data(): void
{
    $service = new UserService();
    $user = $service->create(['name' => 'John', 'email' => 'john@example.com']);

    $this->assertInstanceOf(User::class, $user);
    $this->assertSame('John', $user->name);
}
```

:::

vs.

:::tabs key:stack
== Pest

```php
// This test USES UserService::create but doesn't claim to be its primary test
test('complete registration flow', function () {
    $service = new UserService();
    $user = $service->create(['name' => 'John']);  // Used but not the focus

    $emailService = new EmailService();
    $emailService->sendWelcome($user);  // This is what we're actually testing

    expect($emailSent)->toBeTrue();
})->links(UserService::class.'::create')
  ->linksAndCovers(EmailService::class.'::sendWelcome');
```

== PHPUnit + Attributes

```php
// This test USES UserService::create but doesn't claim to be its primary test
#[Links(UserService::class, 'create')]
#[LinksAndCovers(EmailService::class, 'sendWelcome')]
public function test_complete_registration_flow(): void
{
    $service = new UserService();
    $user = $service->create(['name' => 'John']);  // Used but not the focus

    $emailService = new EmailService();
    $emailService->sendWelcome($user);  // This is what we're actually testing

    $this->assertTrue($emailSent);
}
```

== PHPUnit + @see

```php
/**
 * This test USES UserService::create but doesn't claim to be its primary test
 * @see \App\Services\UserService::create (links only)
 * @see \App\Services\EmailService::sendWelcome (covers)
 */
public function test_complete_registration_flow(): void
{
    $service = new UserService();
    $user = $service->create(['name' => 'John']);  // Used but not the focus

    $emailService = new EmailService();
    $emailService->sendWelcome($user);  // This is what we're actually testing

    $this->assertTrue($emailSent);
}
```

:::

## LinksAndCovers: Primary Coverage

Use `linksAndCovers()` (Pest) or `#[LinksAndCovers]` (PHPUnit) when:

### 1. Unit Tests

The test directly verifies the method's behavior:

:::tabs key:stack
== Pest

```php
test('adds two numbers', function () {
    $calc = new Calculator();
    expect($calc->add(2, 3))->toBe(5);
})->linksAndCovers(Calculator::class.'::add');

test('handles negative numbers', function () {
    $calc = new Calculator();
    expect($calc->add(-2, -3))->toBe(-5);
})->linksAndCovers(Calculator::class.'::add');
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(Calculator::class, 'add')]
public function test_adds_two_numbers(): void
{
    $calc = new Calculator();
    $this->assertSame(5, $calc->add(2, 3));
}

#[LinksAndCovers(Calculator::class, 'add')]
public function test_handles_negative_numbers(): void
{
    $calc = new Calculator();
    $this->assertSame(-5, $calc->add(-2, -3));
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Calculator::add
 */
public function test_adds_two_numbers(): void
{
    $calc = new Calculator();
    $this->assertSame(5, $calc->add(2, 3));
}

/**
 * @see \App\Calculator::add
 */
public function test_handles_negative_numbers(): void
{
    $calc = new Calculator();
    $this->assertSame(-5, $calc->add(-2, -3));
}
```

:::

### 2. The Test "Owns" the Method

If this test fails, the method is broken:

:::tabs key:stack
== Pest

```php
test('validates email format', function () {
    $validator = new UserValidator();

    expect($validator->validateEmail('valid@email.com'))->toBeTrue();
    expect($validator->validateEmail('invalid'))->toBeFalse();
})->linksAndCovers(UserValidator::class.'::validateEmail');
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(UserValidator::class, 'validateEmail')]
public function test_validates_email_format(): void
{
    $validator = new UserValidator();

    $this->assertTrue($validator->validateEmail('valid@email.com'));
    $this->assertFalse($validator->validateEmail('invalid'));
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Validators\UserValidator::validateEmail
 */
public function test_validates_email_format(): void
{
    $validator = new UserValidator();

    $this->assertTrue($validator->validateEmail('valid@email.com'));
    $this->assertFalse($validator->validateEmail('invalid'));
}
```

:::

### 3. Coverage Reports Should Count It

You want this test-method relationship in coverage metrics:

```bash
./vendor/bin/testlink report

UserValidator
└── validateEmail()
    └── UserValidatorTest::validates email format  ← Counted as coverage
```

## Links: Traceability Without Coverage

Use `links()` when:

### 1. Integration Tests

The test exercises the method but isn't its primary test:

:::tabs key:stack
== Pest

```php
test('checkout process creates order', function () {
    // Many methods are called, but we're testing the flow
    $cart = new Cart();
    $cart->add($product);

    $checkout = new CheckoutService();
    $order = $checkout->process($cart);

    expect($order)->toBeInstanceOf(Order::class);
})
->linksAndCovers(CheckoutService::class.'::process')  // This IS what we're testing
->links(Cart::class.'::add');                          // This is just setup
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(CheckoutService::class, 'process')]  // This IS what we're testing
#[Links(Cart::class, 'add')]                           // This is just setup
public function test_checkout_process_creates_order(): void
{
    // Many methods are called, but we're testing the flow
    $cart = new Cart();
    $cart->add($product);

    $checkout = new CheckoutService();
    $order = $checkout->process($cart);

    $this->assertInstanceOf(Order::class, $order);
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\CheckoutService::process (primary test target)
 * @see \App\Cart::add (setup only)
 */
public function test_checkout_process_creates_order(): void
{
    // Many methods are called, but we're testing the flow
    $cart = new Cart();
    $cart->add($product);

    $checkout = new CheckoutService();
    $order = $checkout->process($cart);

    $this->assertInstanceOf(Order::class, $order);
}
```

:::

### 2. E2E Tests

End-to-end tests touch many methods:

:::tabs key:stack
== Pest

```php
test('user can complete purchase', function () {
    // Tests the entire flow
})
->links(UserService::class.'::create')
->links(CartService::class.'::add')
->links(PaymentService::class.'::charge')
->links(OrderService::class.'::create');
// All linked for traceability, none claiming primary coverage
```

== PHPUnit + Attributes

```php
// All linked for traceability, none claiming primary coverage
#[Links(UserService::class, 'create')]
#[Links(CartService::class, 'add')]
#[Links(PaymentService::class, 'charge')]
#[Links(OrderService::class, 'create')]
public function test_user_can_complete_purchase(): void
{
    // Tests the entire flow
}
```

== PHPUnit + @see

```php
/**
 * All linked for traceability, none claiming primary coverage
 * @see \App\Services\UserService::create
 * @see \App\Services\CartService::add
 * @see \App\Services\PaymentService::charge
 * @see \App\Services\OrderService::create
 */
public function test_user_can_complete_purchase(): void
{
    // Tests the entire flow
}
```

:::

### 3. Avoiding Double Coverage

When unit tests already provide coverage:

:::tabs key:stack
== Pest

```php
// Unit test - provides coverage
test('charges credit card', function () {
    $payment = new PaymentService();
    expect($payment->charge(100))->toBeTrue();
})->linksAndCovers(PaymentService::class.'::charge');

// Integration test - just links (coverage already provided above)
test('order payment flow', function () {
    // ...
})->links(PaymentService::class.'::charge');
```

== PHPUnit + Attributes

```php
// Unit test - provides coverage
#[LinksAndCovers(PaymentService::class, 'charge')]
public function test_charges_credit_card(): void
{
    $payment = new PaymentService();
    $this->assertTrue($payment->charge(100));
}

// Integration test - just links (coverage already provided above)
#[Links(PaymentService::class, 'charge')]
public function test_order_payment_flow(): void
{
    // ...
}
```

== PHPUnit + @see

```php
/**
 * Unit test - provides coverage
 * @see \App\Services\PaymentService::charge
 */
public function test_charges_credit_card(): void
{
    $payment = new PaymentService();
    $this->assertTrue($payment->charge(100));
}

/**
 * Integration test - just links (coverage already provided above)
 * @see \App\Services\PaymentService::charge (links only)
 */
public function test_order_payment_flow(): void
{
    // ...
}
```

:::

### 4. Setup/Teardown Methods

Methods used for test setup:

:::tabs key:stack
== Pest

```php
test('sends notification after user update', function () {
    $user = UserFactory::create();  // Setup - not what we're testing

    $service = new UserService();
    $service->update($user, ['name' => 'New Name']);

    expect($notificationSent)->toBeTrue();
})
->links(UserFactory::class.'::create')           // Setup
->linksAndCovers(UserService::class.'::update'); // Actual test subject
```

== PHPUnit + Attributes

```php
#[Links(UserFactory::class, 'create')]           // Setup
#[LinksAndCovers(UserService::class, 'update')]  // Actual test subject
public function test_sends_notification_after_user_update(): void
{
    $user = UserFactory::create();  // Setup - not what we're testing

    $service = new UserService();
    $service->update($user, ['name' => 'New Name']);

    $this->assertTrue($notificationSent);
}
```

== PHPUnit + @see

```php
/**
 * @see \Database\Factories\UserFactory::create (setup)
 * @see \App\Services\UserService::update (actual test subject)
 */
public function test_sends_notification_after_user_update(): void
{
    $user = UserFactory::create();  // Setup - not what we're testing

    $service = new UserService();
    $service->update($user, ['name' => 'New Name']);

    $this->assertTrue($notificationSent);
}
```

:::

## The Coverage Decision

Ask yourself: **"If this test fails, does it mean THIS specific method is broken?"**

- **Yes** → `linksAndCovers()` - The test owns this method
- **No** → `links()` - The test uses this method but doesn't own it

### Example Analysis

:::tabs key:stack
== Pest

```php
test('user registration sends welcome email', function () {
    $userService = new UserService();
    $user = $userService->create(['email' => 'test@example.com']);

    $emailService = new EmailService();
    $emailService->sendWelcome($user);

    expect($emailSent)->toBeTrue();
});
```

== PHPUnit + Attributes

```php
public function test_user_registration_sends_welcome_email(): void
{
    $userService = new UserService();
    $user = $userService->create(['email' => 'test@example.com']);

    $emailService = new EmailService();
    $emailService->sendWelcome($user);

    $this->assertTrue($emailSent);
}
```

== PHPUnit + @see

```php
public function test_user_registration_sends_welcome_email(): void
{
    $userService = new UserService();
    $user = $userService->create(['email' => 'test@example.com']);

    $emailService = new EmailService();
    $emailService->sendWelcome($user);

    $this->assertTrue($emailSent);
}
```

:::

What should we link?

| Method | If test fails... | Use |
|--------|-----------------|-----|
| `UserService::create` | User creation might be broken, OR email sending might be broken | `links()` - not primary focus |
| `EmailService::sendWelcome` | Email sending is broken | `linksAndCovers()` - primary focus |

:::tabs key:stack
== Pest

```php
test('user registration sends welcome email', function () {
    // ...
})
->links(UserService::class.'::create')
->linksAndCovers(EmailService::class.'::sendWelcome');
```

== PHPUnit + Attributes

```php
#[Links(UserService::class, 'create')]
#[LinksAndCovers(EmailService::class, 'sendWelcome')]
public function test_user_registration_sends_welcome_email(): void
{
    // ...
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\UserService::create (links only)
 * @see \App\Services\EmailService::sendWelcome (primary coverage)
 */
public function test_user_registration_sends_welcome_email(): void
{
    // ...
}
```

:::

## Validation Differences

### LinksAndCovers Validation

`testlink validate` checks that `#[TestedBy]` exists on the production side:

:::tabs key:stack
== Pest

```php
// Test
->linksAndCovers(UserService::class.'::create')

// Production SHOULD have
#[TestedBy(UserServiceTest::class, 'creates user')]
public function create(): User
```

== PHPUnit + Attributes

```php
// Test
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void { }

// Production SHOULD have
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
```

== PHPUnit + @see

```php
// Test
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void { }

// Production SHOULD have
/**
 * @see \Tests\UserServiceTest::test_creates_user
 */
public function create(): User
```

:::

If missing, validation reports it as an issue.

### Links Validation

`#[Links]` doesn't require `#[TestedBy]`:

:::tabs key:stack
== Pest

```php
// Test
->links(CartService::class.'::add')

// Production - no TestedBy required
public function add(Product $product): void
```

== PHPUnit + Attributes

```php
// Test
#[Links(CartService::class, 'add')]
public function test_checkout_flow(): void { }

// Production - no TestedBy required
public function add(Product $product): void
```

== PHPUnit + @see

```php
// Test - using @see for links only
/**
 * @see \App\Services\CartService::add (links only)
 */
public function test_checkout_flow(): void { }

// Production - no @see required
public function add(Product $product): void
```

:::

This makes sense: if a method is only *used* by integration tests (not primarily tested), it shouldn't need `#[TestedBy]`.

## Report Output

The report distinguishes between coverage types:

```bash
./vendor/bin/testlink report

UserService
└── create()
    ├── UserServiceTest::test_creates_user (covers)
    └── RegistrationFlowTest::test_complete_flow (links)
```

## Common Patterns

### Pattern 1: Unit + Integration

:::tabs key:stack
== Pest

```php
// Unit test covers the method
test('creates user', function () {
    // Direct test of create()
})->linksAndCovers(UserService::class.'::create');

// Integration test links to it
test('registration flow', function () {
    // Uses create() as part of flow
})->links(UserService::class.'::create');
```

== PHPUnit + Attributes

```php
// Unit test covers the method
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
{
    // Direct test of create()
}

// Integration test links to it
#[Links(UserService::class, 'create')]
public function test_registration_flow(): void
{
    // Uses create() as part of flow
}
```

== PHPUnit + @see

```php
/**
 * Unit test covers the method
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
{
    // Direct test of create()
}

/**
 * Integration test links to it
 * @see \App\Services\UserService::create (links only)
 */
public function test_registration_flow(): void
{
    // Uses create() as part of flow
}
```

:::

### Pattern 2: One Focus, Many Dependencies

:::tabs key:stack
== Pest

```php
test('processes refund', function () {
    // Primary focus is refund
})->linksAndCovers(PaymentService::class.'::refund')
  ->links(PaymentService::class.'::getTransaction')
  ->links(PaymentService::class.'::updateBalance')
  ->links(NotificationService::class.'::notify');
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(PaymentService::class, 'refund')]
#[Links(PaymentService::class, 'getTransaction')]
#[Links(PaymentService::class, 'updateBalance')]
#[Links(NotificationService::class, 'notify')]
public function test_processes_refund(): void
{
    // Primary focus is refund
}
```

== PHPUnit + @see

```php
/**
 * Primary focus is refund
 * @see \App\Services\PaymentService::refund (primary coverage)
 * @see \App\Services\PaymentService::getTransaction
 * @see \App\Services\PaymentService::updateBalance
 * @see \App\Services\NotificationService::notify
 */
public function test_processes_refund(): void
{
    // ...
}
```

:::

### Pattern 3: Feature Tests

:::tabs key:stack
== Pest

```php
test('user can update profile', function () {
    // Feature test - links to all touched code
})->links(UserController::class.'::update')
  ->links(UserService::class.'::update')
  ->links(UserRepository::class.'::save');
// No linksAndCovers - unit tests provide coverage
```

== PHPUnit + Attributes

```php
// No LinksAndCovers - unit tests provide coverage
#[Links(UserController::class, 'update')]
#[Links(UserService::class, 'update')]
#[Links(UserRepository::class, 'save')]
public function test_user_can_update_profile(): void
{
    // Feature test - links to all touched code
}
```

== PHPUnit + @see

```php
/**
 * Feature test - links to all touched code
 * No primary coverage - unit tests provide that
 * @see \App\Http\Controllers\UserController::update
 * @see \App\Services\UserService::update
 * @see \App\Repositories\UserRepository::save
 */
public function test_user_can_update_profile(): void
{
    // ...
}
```

:::

## Summary

| Scenario | Use | Why |
|----------|-----|-----|
| Unit test for a method | `linksAndCovers` | Primary coverage |
| Integration test | `links` | Traceability only |
| E2E test | `links` | Too broad for coverage |
| Setup code | `links` | Not the test focus |
| Already covered elsewhere | `links` | Avoid double counting |

**Rule of thumb:** One method should have one or few tests claiming `linksAndCovers` coverage, but can have many tests using `links` for traceability.

## See Also

- [#[LinksAndCovers] Reference](/reference/attributes/linksandcovers)
- [#[Links] Reference](/reference/attributes/links)
- [Pest Methods Reference](/reference/pest-methods)
