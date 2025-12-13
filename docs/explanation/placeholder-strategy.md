# Placeholder Strategy

Understanding the placeholder system: why it exists, how it works, and when to use it.

## What Are Placeholders?

Placeholders are temporary markers that stand in for real class::method references:

:::tabs key:stack
== Pest

```php
// Instead of this (requires knowing the class name upfront)
->linksAndCovers(UserService::class.'::create')

// You can use this (decide the name later)
->linksAndCovers('@user-create')
```

== PHPUnit + Attributes

```php
// Instead of this (requires knowing the class name upfront)
#[LinksAndCovers(UserService::class, 'create')]

// You can use this (decide the name later)
#[LinksAndCovers('@user-create')]
```

== PHPUnit + @see

```php
// Instead of this (requires knowing the class name upfront)
/** @see \App\Services\UserService::create */

// You can use this (decide the name later)
/** @see @user-create */
```

:::

Placeholders:
- Start with `@` followed by a letter
- Can contain letters, numbers, hyphens, underscores
- Are resolved later with `testlink pair`

## Why Placeholders Exist

### The TDD Timing Problem

In Test-Driven Development, you write the test first:

:::tabs key:stack
== Pest

```php
// Step 1: Write failing test
test('creates user with valid data', function () {
    $service = new UserService();
    $user = $service->create(['name' => 'John']);

    expect($user)->toBeInstanceOf(User::class);
});
```

Now you want to add the link... but to what?

```php
// The class doesn't exist yet!
->linksAndCovers(UserService::class.'::create')  // Error: Class not found
```

== PHPUnit + Attributes

```php
// Step 1: Write failing test
public function test_creates_user_with_valid_data(): void
{
    $service = new UserService();
    $user = $service->create(['name' => 'John']);

    $this->assertInstanceOf(User::class, $user);
}
```

Now you want to add the link... but to what?

```php
// The class doesn't exist yet!
#[LinksAndCovers(UserService::class, 'create')]  // Error: Class not found
```

== PHPUnit + @see

```php
// Step 1: Write failing test
public function test_creates_user_with_valid_data(): void
{
    $service = new UserService();
    $user = $service->create(['name' => 'John']);

    $this->assertInstanceOf(User::class, $user);
}
```

Now you want to add the link... but to what?

```php
// The class doesn't exist yet!
/** @see \App\Services\UserService::create */  // Reference doesn't exist
```

:::

You're stuck:
- Can't link without the class
- Can't create the class without breaking TDD (writing test first)

### Placeholders Solve This

:::tabs key:stack
== Pest

```php
// Step 1: Write test with placeholder
test('creates user with valid data', function () {
    // ...
})->linksAndCovers('@user-create');  // Works! No class needed yet

// Step 2: Write production code with same placeholder
#[TestedBy('@user-create')]
public function create(array $data): User
{
    // ...
}

// Step 3: After both exist, resolve placeholders
./vendor/bin/testlink pair
```

== PHPUnit + Attributes

```php
// Step 1: Write test with placeholder
#[LinksAndCovers('@user-create')]  // Works! No class needed yet
public function test_creates_user_with_valid_data(): void
{
    // ...
}

// Step 2: Write production code with same placeholder
#[TestedBy('@user-create')]
public function create(array $data): User
{
    // ...
}

// Step 3: After both exist, resolve placeholders
./vendor/bin/testlink pair
```

== PHPUnit + @see

```php
// Step 1: Write test with placeholder
/**
 * @see @user-create
 */
public function test_creates_user_with_valid_data(): void
{
    // ...
}

// Step 2: Write production code with same placeholder
#[TestedBy('@user-create')]
public function create(array $data): User
{
    // ...
}

// Step 3: After both exist, resolve placeholders
./vendor/bin/testlink pair
```

:::

## How Placeholders Work

### 1. Marking Phase

Use the same placeholder in test and production:

:::tabs key:stack
== Pest

```php
// tests/UserServiceTest.php
test('creates user', function () {
    // ...
})->linksAndCovers('@A');

// src/UserService.php
#[TestedBy('@A')]
public function create(): User
```

== PHPUnit + Attributes

```php
// tests/UserServiceTest.php
#[LinksAndCovers('@A')]
public function test_creates_user(): void
{
    // ...
}

// src/UserService.php
#[TestedBy('@A')]
public function create(): User
```

== PHPUnit + @see

```php
// tests/UserServiceTest.php
/**
 * @see @A
 */
public function test_creates_user(): void
{
    // ...
}

// src/UserService.php
#[TestedBy('@A')]
public function create(): User
```

:::

### 2. Scanning Phase

`testlink pair` scans for all placeholders:

```
Found placeholders:
  @A
    Tests:
      - tests/UserServiceTest.php :: "creates user"
    Production:
      - src/UserService.php :: create()
```

### 3. Resolution Phase

Placeholders are replaced with real references:

:::tabs key:stack
== Pest

```php
// tests/UserServiceTest.php - AFTER
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');

// src/UserService.php - AFTER
#[TestedBy(UserServiceTest::class, 'creates user')]
public function create(): User
```

== PHPUnit + Attributes

```php
// tests/UserServiceTest.php - AFTER
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
{
    // ...
}

// src/UserService.php - AFTER
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
```

== PHPUnit + @see

```php
// tests/UserServiceTest.php - AFTER
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
{
    // ...
}

// src/UserService.php - AFTER
/**
 * @see \Tests\UserServiceTest::test_creates_user
 */
public function create(): User
```

:::

## Placeholder Naming

### Simple Placeholders

For quick, temporary markers:

:::tabs key:stack
== Pest

```php
->linksAndCovers('@A')
->linksAndCovers('@B')
->linksAndCovers('@C')
```

== PHPUnit + Attributes

```php
#[LinksAndCovers('@A')]
#[LinksAndCovers('@B')]
#[LinksAndCovers('@C')]
```

== PHPUnit + @see

```php
/** @see @A */
/** @see @B */
/** @see @C */
```

:::

Good for:
- Single feature development
- Short-lived placeholders
- When you'll resolve immediately

### Descriptive Placeholders

For longer-lived or team workflows:

:::tabs key:stack
== Pest

```php
->linksAndCovers('@user-create')
->linksAndCovers('@order-process')
->linksAndCovers('@payment-refund')
```

== PHPUnit + Attributes

```php
#[LinksAndCovers('@user-create')]
#[LinksAndCovers('@order-process')]
#[LinksAndCovers('@payment-refund')]
```

== PHPUnit + @see

```php
/** @see @user-create */
/** @see @order-process */
/** @see @payment-refund */
```

:::

Good for:
- Multiple developers
- Placeholders that live for a while
- Self-documenting code

### Valid Placeholder Patterns

| Placeholder | Valid | Notes |
|-------------|-------|-------|
| `@A` | ✓ | Minimal |
| `@user` | ✓ | Lowercase |
| `@UserCreate` | ✓ | PascalCase |
| `@user-create` | ✓ | Kebab-case |
| `@user_create` | ✓ | Snake_case |
| `@user123` | ✓ | With numbers |
| `@123` | ✗ | Must start with letter after @ |
| `@-test` | ✗ | Must start with letter after @ |
| `user` | ✗ | Missing @ |

## N:M Placeholder Matching

One placeholder can match multiple tests AND multiple methods:

:::tabs key:stack
== Pest

```php
// Multiple tests with same placeholder
test('creates user with name', function () {
    // ...
})->linksAndCovers('@user-create');

test('creates user with email', function () {
    // ...
})->linksAndCovers('@user-create');

// Multiple methods with same placeholder
#[TestedBy('@user-create')]
public function create(): User

#[TestedBy('@user-create')]
public function createWithRole(): User
```

After `testlink pair`, ALL tests link to ALL methods:

```php
test('creates user with name', function () {
    // ...
})
->linksAndCovers(UserService::class.'::create')
->linksAndCovers(UserService::class.'::createWithRole');

test('creates user with email', function () {
    // ...
})
->linksAndCovers(UserService::class.'::create')
->linksAndCovers(UserService::class.'::createWithRole');
```

== PHPUnit + Attributes

```php
// Multiple tests with same placeholder
#[LinksAndCovers('@user-create')]
public function test_creates_user_with_name(): void { }

#[LinksAndCovers('@user-create')]
public function test_creates_user_with_email(): void { }

// Multiple methods with same placeholder
#[TestedBy('@user-create')]
public function create(): User

#[TestedBy('@user-create')]
public function createWithRole(): User
```

After `testlink pair`, ALL tests link to ALL methods:

```php
#[LinksAndCovers(UserService::class, 'create')]
#[LinksAndCovers(UserService::class, 'createWithRole')]
public function test_creates_user_with_name(): void { }

#[LinksAndCovers(UserService::class, 'create')]
#[LinksAndCovers(UserService::class, 'createWithRole')]
public function test_creates_user_with_email(): void { }
```

== PHPUnit + @see

```php
// Multiple tests with same placeholder
/** @see @user-create */
public function test_creates_user_with_name(): void { }

/** @see @user-create */
public function test_creates_user_with_email(): void { }

// Multiple methods with same placeholder
#[TestedBy('@user-create')]
public function create(): User

#[TestedBy('@user-create')]
public function createWithRole(): User
```

After `testlink pair`, ALL tests link to ALL methods:

```php
/**
 * @see \App\Services\UserService::create
 * @see \App\Services\UserService::createWithRole
 */
public function test_creates_user_with_name(): void { }

/**
 * @see \App\Services\UserService::create
 * @see \App\Services\UserService::createWithRole
 */
public function test_creates_user_with_email(): void { }
```

:::

This is intentional for cases where multiple tests cover multiple related methods.

## When to Use Placeholders

### Ideal Scenarios

**TDD Development**

:::tabs key:stack
== Pest

```php
// RED: Write failing test
test('calculates discount', function () {
    // ...
})->linksAndCovers('@discount-calc');

// GREEN: Implement
#[TestedBy('@discount-calc')]
public function calculate(): float

// REFACTOR + PAIR: Clean up and resolve
./vendor/bin/testlink pair
```

== PHPUnit + Attributes

```php
// RED: Write failing test
#[LinksAndCovers('@discount-calc')]
public function test_calculates_discount(): void { }

// GREEN: Implement
#[TestedBy('@discount-calc')]
public function calculate(): float

// REFACTOR + PAIR: Clean up and resolve
./vendor/bin/testlink pair
```

== PHPUnit + @see

```php
// RED: Write failing test
/** @see @discount-calc */
public function test_calculates_discount(): void { }

// GREEN: Implement
#[TestedBy('@discount-calc')]
public function calculate(): float

// REFACTOR + PAIR: Clean up and resolve
./vendor/bin/testlink pair
```

:::

**BDD Development**

:::tabs key:stack
== Pest

```php
// Acceptance test placeholder
test('user sees discount on cart', function () {
    // ...
})->linksAndCovers('@cart-discount');

// Multiple implementations use same placeholder
#[TestedBy('@cart-discount')]
public function calculateDiscount(): float

#[TestedBy('@cart-discount')]
public function applyDiscount(): void
```

== PHPUnit + Attributes

```php
// Acceptance test placeholder
#[LinksAndCovers('@cart-discount')]
public function test_user_sees_discount_on_cart(): void { }

// Multiple implementations use same placeholder
#[TestedBy('@cart-discount')]
public function calculateDiscount(): float

#[TestedBy('@cart-discount')]
public function applyDiscount(): void
```

== PHPUnit + @see

```php
// Acceptance test placeholder
/** @see @cart-discount */
public function test_user_sees_discount_on_cart(): void { }

// Multiple implementations use same placeholder
#[TestedBy('@cart-discount')]
public function calculateDiscount(): float

#[TestedBy('@cart-discount')]
public function applyDiscount(): void
```

:::

**Prototyping**

:::tabs key:stack
== Pest

```php
// Quick iteration, resolve later
->linksAndCovers('@A')
->linksAndCovers('@B')
->linksAndCovers('@C')
```

== PHPUnit + Attributes

```php
// Quick iteration, resolve later
#[LinksAndCovers('@A')]
#[LinksAndCovers('@B')]
#[LinksAndCovers('@C')]
```

== PHPUnit + @see

```php
// Quick iteration, resolve later
/** @see @A */
/** @see @B */
/** @see @C */
```

:::

### When NOT to Use Placeholders

**Existing Code**

:::tabs key:stack
== Pest

```php
// Class already exists - use real reference
->linksAndCovers(ExistingService::class.'::method')
```

== PHPUnit + Attributes

```php
// Class already exists - use real reference
#[LinksAndCovers(ExistingService::class, 'method')]
```

== PHPUnit + @see

```php
// Class already exists - use real reference
/** @see \App\Services\ExistingService::method */
```

:::

**Stable Codebase**
```php
// No TDD workflow - use direct links
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
```

**Team Without Convention**
```php
// If team isn't using placeholders, don't introduce
// Use testlink sync instead
```

## Placeholder Lifecycle

```
┌─────────────────┐
│  Development    │
│  Start          │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Write Test     │  test('...')->linksAndCovers('@A')
│  with @         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Write Prod     │  #[TestedBy('@A')]
│  with @         │  public function method()
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Tests Pass     │  Green phase complete
│                 │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  testlink pair  │  Resolve @ → real references
│                 │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Commit         │  No placeholders in repo
│                 │
└─────────────────┘
```

## Pair Command Options

### Preview Changes

```bash
./vendor/bin/testlink pair --dry-run
```

Shows what would change without modifying files.

### Specific Placeholder

```bash
./vendor/bin/testlink pair --placeholder=@user-create
```

Only resolve one placeholder.

### Path Filtering

```bash
./vendor/bin/testlink pair --path=src/Services
```

Only resolve in specific directory.

## CI Integration

Ensure no placeholders in committed code:

```yaml
# .github/workflows/ci.yml
- run: ./vendor/bin/testlink pair
  # Returns exit code 1 if unresolved placeholders exist
```

Or use validate:

```yaml
- run: ./vendor/bin/testlink validate
  # Fails if placeholders remain
```

## Comparison with Sync

| Feature | Placeholder (pair) | Sync |
|---------|-------------------|------|
| Use case | TDD/BDD workflow | Existing code |
| Requires markers | Yes (@placeholder) | No |
| Direction | Both simultaneously | Production → Test or Test → Production |
| When | During development | After development |

### Use Placeholders When

- Following TDD/BDD
- Want to defer class naming
- Building test + production together

### Use Sync When

- Adding links to existing code
- One-way synchronization
- No placeholder markers exist

## Summary

Placeholders enable true test-first development by letting you:

1. Write test code before production code exists
2. Link them with temporary markers
3. Resolve to real references when ready

They're a bridge between "test first" methodology and TestLink's requirement for real class references.

## See Also

- [How-to: Resolve Placeholders](/how-to/resolve-placeholders)
- [How-to: Handle N:M Relationships](/how-to/handle-nm-relationships)
- [Tutorial: TDD with Placeholders](/tutorials/tdd/placeholder-tdd)
- [CLI: pair Command](/reference/cli/pair)
