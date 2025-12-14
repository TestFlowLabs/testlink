# Double-Loop TDD

This tutorial teaches you the double-loop TDD/BDD approach and how to use TestLink to maintain traceability at both levels.

## What is Double-Loop TDD?

Double-loop TDD consists of two nested cycles:

1. **Outer loop (Acceptance)** - High-level tests that describe behavior
2. **Inner loop (Unit)** - Low-level tests that verify implementation

The outer loop drives the inner loop. You write a failing acceptance test, then use unit tests to build the implementation that makes it pass.

## The Two Loops

```
OUTER LOOP (Acceptance)
├── Write failing acceptance test
├── INNER LOOP (Unit) - Repeat until acceptance test passes
│   ├── Write failing unit test
│   ├── Write minimal code to pass
│   └── Refactor
├── Acceptance test now passes
└── Refactor acceptance test if needed
```

## Tutorial: User Registration Feature

Let's build a user registration feature using double-loop TDD.

### Outer Loop: Write Failing Acceptance Test

Start with a high-level test that describes the desired behavior:

:::tabs key:stack
== Pest

```php
<?php
// tests/Feature/UserRegistrationTest.php

use App\Services\UserRegistration;
use App\Models\User;

test('user can register with valid details', function () {
    // Given a registration service
    $registration = new UserRegistration();

    // When a user registers with valid details
    $user = $registration->register([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
    ]);

    // Then a user is created
    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john@example.com');
})->links(UserRegistration::class.'::register');
```

== PHPUnit + Attributes

```php
<?php
// tests/Feature/UserRegistrationTest.php

namespace Tests\Feature;

use App\Services\UserRegistration;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\Links;

class UserRegistrationTest extends TestCase
{
    #[Links(UserRegistration::class, 'register')]
    public function test_user_can_register_with_valid_details(): void
    {
        $registration = new UserRegistration();

        $user = $registration->register([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/Feature/UserRegistrationTest.php

namespace Tests\Feature;

use App\Services\UserRegistration;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRegistrationTest extends TestCase
{
    /**
     * @see \App\Services\UserRegistration::register
     */
    public function test_user_can_register_with_valid_details(): void
    {
        $registration = new UserRegistration();

        $user = $registration->register([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
    }
}
```

:::

::: info Using `links()` / `#[Links]` for Acceptance Tests
We use `links()` (Pest) or `#[Links]` (PHPUnit) instead of `linksAndCovers()` / `#[LinksAndCovers]` for acceptance tests. This creates traceability without affecting code coverage metrics. Unit tests will provide the actual coverage.
:::

Run the test—it fails because nothing exists yet:

```bash
./vendor/bin/pest tests/Feature
# Error: Class "App\Services\UserRegistration" not found
```

### Inner Loop 1: Validate Email

The acceptance test needs `UserRegistration::register()`. Let's build it piece by piece.

**Step 1: Unit Test for Email Validation**

:::tabs key:stack
== Pest

```php
<?php
// tests/Unit/UserRegistrationTest.php

use App\Services\UserRegistration;

describe('UserRegistration', function () {
    describe('validateEmail', function () {
        test('accepts valid email', function () {
            $registration = new UserRegistration();

            expect($registration->validateEmail('test@example.com'))->toBeTrue();
        })->linksAndCovers(UserRegistration::class.'::validateEmail');

        test('rejects invalid email', function () {
            $registration = new UserRegistration();

            expect($registration->validateEmail('invalid'))->toBeFalse();
        })->linksAndCovers(UserRegistration::class.'::validateEmail');
    });
});
```

== PHPUnit + Attributes

```php
<?php
// tests/Unit/UserRegistrationUnitTest.php

namespace Tests\Unit;

use App\Services\UserRegistration;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserRegistrationUnitTest extends TestCase
{
    #[LinksAndCovers(UserRegistration::class, 'validateEmail')]
    public function test_accepts_valid_email(): void
    {
        $registration = new UserRegistration();

        $this->assertTrue($registration->validateEmail('test@example.com'));
    }

    #[LinksAndCovers(UserRegistration::class, 'validateEmail')]
    public function test_rejects_invalid_email(): void
    {
        $registration = new UserRegistration();

        $this->assertFalse($registration->validateEmail('invalid'));
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/Unit/UserRegistrationUnitTest.php

namespace Tests\Unit;

use App\Services\UserRegistration;
use PHPUnit\Framework\TestCase;

class UserRegistrationUnitTest extends TestCase
{
    /**
     * @see \App\Services\UserRegistration::validateEmail
     */
    public function test_accepts_valid_email(): void
    {
        $registration = new UserRegistration();

        $this->assertTrue($registration->validateEmail('test@example.com'));
    }

    /**
     * @see \App\Services\UserRegistration::validateEmail
     */
    public function test_rejects_invalid_email(): void
    {
        $registration = new UserRegistration();

        $this->assertFalse($registration->validateEmail('invalid'));
    }
}
```

:::

**Step 2: Implement Email Validation**

```php
<?php
// src/Services/UserRegistration.php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;

class UserRegistration
{
    #[TestedBy('Tests\Unit\UserRegistrationTest', 'accepts valid email')]
    #[TestedBy('Tests\Unit\UserRegistrationTest', 'rejects invalid email')]
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
```

**Step 3: Run Unit Tests**

```bash
./vendor/bin/pest tests/Unit
# ✓ accepts valid email
# ✓ rejects invalid email
```

### Inner Loop 2: Hash Password

**Step 1: Unit Test for Password Hashing**

:::tabs key:stack
== Pest

```php
describe('hashPassword', function () {
    test('returns hashed password', function () {
        $registration = new UserRegistration();

        $hashed = $registration->hashPassword('secret123');

        expect($hashed)->not->toBe('secret123');
        expect(password_verify('secret123', $hashed))->toBeTrue();
    })->linksAndCovers(UserRegistration::class.'::hashPassword');
});
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(UserRegistration::class, 'hashPassword')]
public function test_returns_hashed_password(): void
{
    $registration = new UserRegistration();

    $hashed = $registration->hashPassword('secret123');

    $this->assertNotSame('secret123', $hashed);
    $this->assertTrue(password_verify('secret123', $hashed));
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\UserRegistration::hashPassword
 */
public function test_returns_hashed_password(): void
{
    $registration = new UserRegistration();

    $hashed = $registration->hashPassword('secret123');

    $this->assertNotSame('secret123', $hashed);
    $this->assertTrue(password_verify('secret123', $hashed));
}
```

:::

**Step 2: Implement Password Hashing**

```php
#[TestedBy('Tests\Unit\UserRegistrationTest', 'returns hashed password')]
public function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}
```

### Inner Loop 3: Create User

**Step 1: Unit Test for User Creation**

:::tabs key:stack
== Pest

```php
describe('createUser', function () {
    test('creates user with validated data', function () {
        $registration = new UserRegistration();

        $user = $registration->createUser(
            name: 'John Doe',
            email: 'john@example.com',
            passwordHash: 'hashed_password'
        );

        expect($user)->toBeInstanceOf(User::class);
        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
    })->linksAndCovers(UserRegistration::class.'::createUser');
});
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(UserRegistration::class, 'createUser')]
public function test_creates_user_with_validated_data(): void
{
    $registration = new UserRegistration();

    $user = $registration->createUser(
        name: 'John Doe',
        email: 'john@example.com',
        passwordHash: 'hashed_password'
    );

    $this->assertInstanceOf(User::class, $user);
    $this->assertSame('John Doe', $user->name);
    $this->assertSame('john@example.com', $user->email);
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\UserRegistration::createUser
 */
public function test_creates_user_with_validated_data(): void
{
    $registration = new UserRegistration();

    $user = $registration->createUser(
        name: 'John Doe',
        email: 'john@example.com',
        passwordHash: 'hashed_password'
    );

    $this->assertInstanceOf(User::class, $user);
    $this->assertSame('John Doe', $user->name);
    $this->assertSame('john@example.com', $user->email);
}
```

:::

**Step 2: Implement User Creation**

```php
#[TestedBy('Tests\Unit\UserRegistrationTest', 'creates user with validated data')]
public function createUser(string $name, string $email, string $passwordHash): User
{
    return new User(
        name: $name,
        email: $email,
        passwordHash: $passwordHash
    );
}
```

### Inner Loop 4: Implement Register (Orchestration)

**Step 1: Unit Test for Register**

:::tabs key:stack
== Pest

```php
describe('register', function () {
    test('validates email before registration', function () {
        $registration = new UserRegistration();

        expect(fn () => $registration->register([
            'name' => 'John',
            'email' => 'invalid',
            'password' => 'secret',
        ]))->toThrow(InvalidArgumentException::class);
    })->linksAndCovers(UserRegistration::class.'::register');

    test('hashes password and creates user', function () {
        $registration = new UserRegistration();

        $user = $registration->register([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret',
        ]);

        expect($user->name)->toBe('John');
        expect(password_verify('secret', $user->passwordHash))->toBeTrue();
    })->linksAndCovers(UserRegistration::class.'::register');
});
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(UserRegistration::class, 'register')]
public function test_validates_email_before_registration(): void
{
    $registration = new UserRegistration();

    $this->expectException(InvalidArgumentException::class);

    $registration->register([
        'name' => 'John',
        'email' => 'invalid',
        'password' => 'secret',
    ]);
}

#[LinksAndCovers(UserRegistration::class, 'register')]
public function test_hashes_password_and_creates_user(): void
{
    $registration = new UserRegistration();

    $user = $registration->register([
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'secret',
    ]);

    $this->assertSame('John', $user->name);
    $this->assertTrue(password_verify('secret', $user->passwordHash));
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\UserRegistration::register
 */
public function test_validates_email_before_registration(): void
{
    $registration = new UserRegistration();

    $this->expectException(InvalidArgumentException::class);

    $registration->register([
        'name' => 'John',
        'email' => 'invalid',
        'password' => 'secret',
    ]);
}

/**
 * @see \App\Services\UserRegistration::register
 */
public function test_hashes_password_and_creates_user(): void
{
    $registration = new UserRegistration();

    $user = $registration->register([
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'secret',
    ]);

    $this->assertSame('John', $user->name);
    $this->assertTrue(password_verify('secret', $user->passwordHash));
}
```

:::

**Step 2: Implement Register**

```php
#[TestedBy('Tests\Unit\UserRegistrationTest', 'validates email before registration')]
#[TestedBy('Tests\Unit\UserRegistrationTest', 'hashes password and creates user')]
#[TestedBy('Tests\Feature\UserRegistrationTest', 'user can register with valid details')]
public function register(array $data): User
{
    if (!$this->validateEmail($data['email'])) {
        throw new \InvalidArgumentException('Invalid email');
    }

    $passwordHash = $this->hashPassword($data['password']);

    return $this->createUser(
        name: $data['name'],
        email: $data['email'],
        passwordHash: $passwordHash
    );
}
```

### Back to Outer Loop

Now run the acceptance test:

```bash
./vendor/bin/pest tests/Feature
# ✓ user can register with valid details
```

The acceptance test passes!

## Final Validation

```bash
./vendor/bin/testlink validate
```

```
Validation Report
─────────────────

Link Summary
  PHPUnit attribute links: 6
  Pest method chain links: 0
  Total links: 6

✓ All links are valid!
```

## Key Takeaways

1. **Start with acceptance test** - Describe the behavior you want
2. **Use `links()` / `#[Links]` for acceptance tests** - Traceability without coverage impact
3. **Build with unit tests** - Drive implementation piece by piece
4. **Use `linksAndCovers()` / `#[LinksAndCovers]` for unit tests** - Full traceability and coverage

## What's Next?

- [Acceptance to Unit](./acceptance-to-unit) - More patterns for driving unit tests
- [Placeholder BDD](./placeholders) - Using placeholders in BDD workflows
