# Bidirectional Linking

**The goal: Cmd+Click to navigate between tests and production code.**

Bidirectional linking makes this possible by creating explicit, clickable connections in both directions.

## Why It Matters

### The Navigation Problem

In most codebases, finding related tests requires searching:

```php
class UserService
{
    public function create(array $data): User
    {
        // Which tests verify this method?
        // Is it tested at all?
        // Time to search through test files...
    }
}
```

With bidirectional links, just **Cmd+Click**:

```php
class UserService
{
    /**
     * @see \Tests\UserServiceTest::test_creates_user      ← Click!
     * @see \Tests\UserServiceTest::test_validates_email   ← Click!
     */
    public function create(array $data): User
    {
        // Two tests verify this. Click to jump directly.
    }
}
```

The same works in reverse—from tests to production:

```php
/**
 * @see \App\Services\UserService::create   ← Click!
 */
public function test_creates_user(): void
{
    // This test covers UserService::create. Click to jump.
}
```

### See All Relationships at a Glance

Without links, you have to hunt for relationships. With links, they're visible instantly:

```php
/**
 * @see \Tests\Unit\OrderServiceTest::test_creates_order
 * @see \Tests\Unit\OrderServiceTest::test_validates_items
 * @see \Tests\Unit\OrderServiceTest::test_calculates_total
 * @see \Tests\Feature\OrderFlowTest::test_complete_checkout
 */
public function create(array $items): Order
{
    // Four tests verify this method.
    // Two are unit tests, two are feature tests.
    // All visible here, all clickable.
}
```

## How It Works

### Both Directions Required

Bidirectional means links exist on **both sides**:

```
Production → Test:  @see tags point to tests
Test → Production:  @see tags (or attributes) point to production
```

Why both? Each direction answers a different question:

| From | You want to know | Link direction |
|------|-----------------|----------------|
| Production code | "What tests verify this?" | Prod → Test |
| Test code | "What does this test cover?" | Test → Prod |

With both directions, navigation works from anywhere.

### Production Side Links

Add `@see` tags pointing to tests:

```php
class UserService
{
    /**
     * @see \Tests\UserServiceTest::test_creates_user
     * @see \Tests\UserServiceTest::test_validates_email
     */
    public function create(array $data): User
    {
        // ...
    }
}
```

### Test Side Links

:::tabs key:stack
== Pest

```php
/**
 * @see \App\Services\UserService::create
 */
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

== PHPUnit + Attributes

```php
/**
 * @see \App\Services\UserService::create
 */
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
{
    // ...
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
{
    // ...
}
```

:::

## Keeping Links Valid

Links break when code changes. TestLink catches this:

```bash
$ ./vendor/bin/testlink validate

  ✗ Broken link
    UserService::create
      → UserServiceTest::test_old_name (test not found)

  ✗ Missing link
    UserServiceTest::test_creates_user
      → UserService::create (no @see in production)
```

Run validation in CI/CD to ensure navigation links stay accurate:

```yaml
- run: ./vendor/bin/testlink validate
```

## Generating Links Automatically

Don't maintain links by hand. Use sync:

```bash
$ ./vendor/bin/testlink sync

  Adding @see tags
    ✓ UserService::create
      + @see UserServiceTest::test_creates_user
      + @see UserServiceTest::test_validates_email
```

This adds `@see` tags based on your test declarations, keeping both sides synchronized.

## Additional Benefits

### Refactoring Confidence

When you rename a method, validation tells you exactly what breaks:

```bash
$ ./vendor/bin/testlink validate

  ✗ UserServiceTest::test_creates_user
      → linksAndCovers(UserService::create) - method not found

  Did you rename UserService::create?
```

### Living Documentation

The links document your test coverage:

```php
/**
 * @see \Tests\UserServiceTest::test_creates_user
 * @see \Tests\UserServiceTest::test_creates_user_validates_email
 * @see \Tests\UserServiceTest::test_creates_user_hashes_password
 * @see \Tests\UserFlowTest::test_registration_flow
 */
public function create(array $data): User
```

Reading the `@see` tags tells you:
- The method is tested
- What aspects are tested (email validation, password hashing)
- It's part of a larger flow (registration)

### Coverage Reports

See all relationships at once:

```bash
$ ./vendor/bin/testlink report

  UserService
    create()
      → UserServiceTest::test_creates_user
      → UserServiceTest::test_validates_email
    update()
      → UserServiceTest::test_updates_user
    delete()
      → (no tests linked)  ← Gap visible immediately
```

## When to Use

### Always Link

- **Unit tests** — Direct method-to-test relationships
- **Core business logic** — Critical code that must be tested
- **Public APIs** — Methods others depend on

### Consider Linking

- **Integration tests** — Use `#[Links]` for secondary coverage
- **Helper methods** — May not need individual links

### Skip Linking

- **Trivial code** — Getters/setters without logic
- **Generated code** — Auto-generated files

## Summary

Bidirectional linking exists for one primary purpose: **Cmd+Click navigation between tests and production code.**

Everything else—validation, sync, reports—supports this by keeping your navigation links accurate and up-to-date.

The result: No more searching for tests. Just click.

## See Also

- [Tutorial: First Bidirectional Link](/tutorials/first-bidirectional-link)
- [Test Traceability](./test-traceability)
- [Keep Links Valid](/how-to/run-validation-in-ci)
