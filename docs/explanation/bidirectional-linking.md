# Bidirectional Linking

What is bidirectional linking and why it matters for test maintainability.

## The Problem

In most codebases, the relationship between tests and production code is implicit:

```
src/UserService.php          tests/UserServiceTest.php
├── create()                 ├── test_creates_user()
├── update()                 ├── test_updates_user()
└── delete()                 └── test_deletes_user()
```

The connection exists only through:
- Naming conventions (`UserService` → `UserServiceTest`)
- Developer knowledge
- Hope that someone documented it

This creates several problems:

### 1. Lost Context

When reading production code, you can't easily find its tests:

```php
class UserService
{
    // Which tests verify this method works correctly?
    // Is it tested at all?
    public function create(array $data): User
    {
        // ...
    }
}
```

### 2. Orphaned Tests

When production code changes, tests can become orphaned:

```php
// Production: method was renamed
public function createUser(array $data): User  // was: create()

// Test: still references old name mentally
public function test_creates_user(): void
{
    // This test might still pass but no longer tests what we think
}
```

### 3. Missing Coverage

Without explicit links, it's hard to know what's tested:

```php
class OrderService
{
    public function process(): void { }      // Tested? Maybe?
    public function validate(): void { }     // Tested? Who knows?
    public function calculateTax(): void { } // Probably tested... somewhere?
}
```

## The Solution: Bidirectional Links

TestLink creates explicit, verifiable links in both directions:

```
Production → Test:  "This method is tested by these tests"
Test → Production:  "This test covers these methods"
```

### Production Side

```php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'test_creates_user')]
    #[TestedBy(UserServiceTest::class, 'test_creates_user_with_role')]
    public function create(array $data): User
    {
        // Now we KNOW exactly which tests verify this method
    }
}
```

### Test Side

:::tabs key:stack
== Pest

```php
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
// Now we KNOW exactly what production code this test covers
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
{
    // Now we KNOW exactly what production code this test covers
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
{
    // Now we KNOW exactly what production code this test covers
}
```

:::

## Why Bidirectional?

### One Direction Isn't Enough

You might think one direction is sufficient:

:::tabs key:stack
== Pest

```php
// Only test → production?
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

But then you can't answer: "What tests cover `UserService::create`?" without searching through all test files.

== PHPUnit + Attributes

```php
// Only test → production?
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void { }
```

But then you can't answer: "What tests cover `UserService::create`?" without searching through all test files.

== PHPUnit + @see

```php
// Only test → production?
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void { }
```

But then you can't answer: "What tests cover `UserService::create`?" without searching through all test files.

:::

```php
// Only production → test?
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(array $data): User
```

But then you can't answer: "What does this test actually test?" without reading the test file.

### Bidirectional Enables Verification

With both directions, TestLink can verify they match:

```bash
./vendor/bin/testlink validate
```

```
✓ UserService::create
  ↔ UserServiceTest::test_creates_user (synchronized)

✗ OrderService::process
  → OrderServiceTest::test_processes_order (missing test link)
```

If either side is missing or mismatched, validation fails.

## Benefits

### 1. Navigable Code

IDEs can follow the links:

```php
/**
 * @see \Tests\UserServiceTest::test_creates_user  ← Click to jump
 */
public function create(array $data): User
```

```php
/**
 * @see \App\UserService::create  ← Click to jump
 */
public function test_creates_user(): void
```

### 2. Verifiable Coverage

Run validation in CI to ensure all links are synchronized:

```yaml
- run: ./vendor/bin/testlink validate
```

If someone adds a test without linking, or removes a method without updating tests, CI fails.

### 3. Discoverable Relationships

View all relationships at once:

```bash
./vendor/bin/testlink report
```

```
UserService
├── create()
│   ├── UserServiceTest::test_creates_user
│   └── UserServiceTest::test_creates_user_with_role
├── update()
│   └── UserServiceTest::test_updates_user
└── delete()
    └── (no tests)  ← Immediately visible gap
```

### 4. Refactoring Confidence

When renaming or moving code, the links tell you exactly what needs updating:

```bash
# After renaming UserService::create to UserService::createUser
./vendor/bin/testlink validate

✗ Validation failed:
  UserServiceTest::test_creates_user
    → linksAndCovers(UserService::create) - method not found
```

### 5. Documentation

Links serve as living documentation:

```php
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
#[TestedBy(UserServiceTest::class, 'test_creates_user_validates_email')]
#[TestedBy(UserServiceTest::class, 'test_creates_user_hashes_password')]
#[TestedBy(UserFlowTest::class, 'test_registration_flow')]
public function create(array $data): User
```

Just by reading the attributes, you understand:
- The method is tested
- What aspects are tested (email validation, password hashing)
- It's part of a larger flow (registration)

## The Bidirectional Contract

TestLink enforces a contract:

| Production Says | Test Says | Status |
|-----------------|-----------|--------|
| "Tested by X" | "Covers Y" | ✗ Mismatch |
| "Tested by X" | "Covers X" | ✓ Valid |
| Nothing | "Covers X" | ⚠ Warning |
| "Tested by X" | Nothing | ✗ Orphan |

This contract ensures your test documentation stays accurate as code evolves.

## When to Use Bidirectional Links

### Always Use For

- **Unit tests** - Direct method-to-test relationships
- **Core business logic** - Critical code that must be tested
- **Public APIs** - Methods others depend on

### Consider For

- **Integration tests** - Use `#[Links]` for secondary coverage
- **Helper methods** - May not need individual links

### Skip For

- **Trivial code** - Getters/setters without logic
- **Generated code** - Auto-generated files
- **Third-party wrappers** - Simple delegation

## Summary

Bidirectional linking creates explicit, verifiable connections between your tests and production code. This transforms implicit "I think this is tested" into explicit "This IS tested by THESE tests, and I can prove it."

The result is a more maintainable, navigable, and trustworthy test suite.

## See Also

- [Tutorial: First Bidirectional Link](/tutorials/first-bidirectional-link)
- [Test Traceability](./test-traceability)
- [#[TestedBy] Reference](/reference/attributes/testedby)
