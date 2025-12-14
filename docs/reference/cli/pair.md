# pair

Resolve placeholder markers to real class and method references.

## Synopsis

```bash
testlink pair [options]
```

## Description

The `pair` command:

1. Scans for placeholder markers (e.g., `@user-create`)
2. Finds matching production methods and tests
3. Replaces placeholders with real references
4. Creates N:M links when multiple matches exist

## Options

| Option | Description |
|--------|-------------|
| `--dry-run` | Preview changes without modifying files |
| `--placeholder=<name>` | Only resolve specific placeholder |
| `--verbose`, `-v` | Show detailed information |
| `--path=<path>` | Filter by directory or file path |

## Examples

### Preview pairing

```bash
./vendor/bin/testlink pair --dry-run
```

Output:
```
  Pairing Placeholders
  ────────────────────
  Running in dry-run mode. No files will be modified.

  Scanning for placeholders...


  Found Placeholders
    ✓ @user-create    1 production × 2 tests = 2 links
    ✓ @order-process  2 production × 3 tests = 6 links


  Production Files
    src/Services/UserService.php
      @user-create → UserServiceTest::test_creates_user
      @user-create → UserServiceTest::test_validates_email
    src/Services/OrderService.php
      @order-process → OrderServiceTest::test_creates_order
      @order-process → OrderServiceTest::test_processes_payment


  Test Files
    tests/UserServiceTest.php
      @user-create → UserService::create
    tests/OrderServiceTest.php
      @order-process → OrderService::create
      @order-process → OrderService::process

  Summary (dry-run)
  ─────────────────
    Placeholders resolved:    2
    Total changes:            8
    Files modified:           4

  ✓ Dry-run complete. Use --no-dry-run to apply.
```

### Apply pairing

```bash
./vendor/bin/testlink pair
```

Output:
```
  Pairing Placeholders
  ────────────────────
  Scanning for placeholders...


  Found Placeholders
    ✓ @user-create    1 production × 2 tests = 2 links
    ✓ @order-process  2 production × 3 tests = 6 links


  Production Files
    src/Services/UserService.php
      @user-create → UserServiceTest::test_creates_user
      @user-create → UserServiceTest::test_validates_email
    src/Services/OrderService.php
      @order-process → OrderServiceTest::test_creates_order
      @order-process → OrderServiceTest::test_processes_payment


  Test Files
    tests/UserServiceTest.php
      @user-create → UserService::create
    tests/OrderServiceTest.php
      @order-process → OrderService::create
      @order-process → OrderService::process

  Summary
  ───────
    Placeholders resolved:    2
    Total changes:            8
    Files modified:           4

  ✓ Pairing complete.
```

### Resolve specific placeholder

```bash
./vendor/bin/testlink pair --placeholder=@user-create
```

Only resolves the `@user-create` placeholder.

### Filter by path

```bash
./vendor/bin/testlink pair --path=src/Services
```

## Placeholder Syntax

Valid placeholders must:
- Start with `@` (for attributes) or `@@` (for `@see` tags)
- Followed by a letter
- Can contain letters, numbers, hyphens, underscores

### Single `@` prefix (attributes)

These placeholders resolve to PHP attributes:
- `@A` - Single letter
- `@user-create` - Kebab case
- `@UserCreate` - Pascal case
- `@user_create` - Snake case

### Double `@@` prefix (`@see` tags)

These placeholders resolve to PHPDoc `@see` tags with FQCN:
- `@@A` - Resolves to `/** @see \Namespace\Class::method */`
- `@@user-create` - Same, with FQCN format

::: warning PHPUnit only
The `@@` prefix is only supported with PHPUnit tests. Pest tests do not support `@see` tags. Using `@@` with Pest will result in an error.
:::

### Invalid syntax
- `@123` - Must start with letter
- `@-test` - Must start with letter
- `user` - Must start with @

## N:M Resolution

Placeholders create N:M (many-to-many) links:

```
1 production method + 3 tests = 3 links
2 production methods + 3 tests = 6 links
```

### Before resolution

```php
// Production (2 methods)
#[TestedBy('@checkout')]
public function validate() { }

#[TestedBy('@checkout')]
public function process() { }

// Tests (3 tests)
test('validates order')->linksAndCovers('@checkout');
test('processes payment')->linksAndCovers('@checkout');
test('sends confirmation')->linksAndCovers('@checkout');
```

### After resolution (2 × 3 = 6 links)

```php
// Production
#[TestedBy('Tests\CheckoutTest', 'validates order')]
#[TestedBy('Tests\CheckoutTest', 'processes payment')]
#[TestedBy('Tests\CheckoutTest', 'sends confirmation')]
public function validate() { }

#[TestedBy('Tests\CheckoutTest', 'validates order')]
#[TestedBy('Tests\CheckoutTest', 'processes payment')]
#[TestedBy('Tests\CheckoutTest', 'sends confirmation')]
public function process() { }

// Tests
test('validates order')
    ->linksAndCovers(CheckoutService::class.'::validate')
    ->linksAndCovers(CheckoutService::class.'::process');
```

## Using `@@` Prefix for `@see` Tags

The `@@` prefix generates PHPDoc `@see` tags instead of attributes. This is useful when you prefer documentation-style links over attributes.

::: warning PHPUnit Only
The `@@` prefix only works with PHPUnit tests. Pest tests do not support `@see` tags.
:::

### Before resolution

```php
// Production
#[TestedBy('@@user-create')]
public function create(): User { }

// Test (PHPUnit)
#[LinksAndCovers('@@user-create')]
public function testCreatesUser(): void { }
```

### After resolution

```php
// Production - @see tag with FQCN
/** @see \Tests\Unit\UserServiceTest::testCreatesUser */
public function create(): User { }

// Test - @see tag with FQCN
/** @see \App\Services\UserService::create */
public function testCreatesUser(): void { }
```

### Error with Pest

Using `@@` with Pest results in an error:

```
Error: Placeholder @@user-create uses @@prefix (for @see tags) but Pest tests
do not support @see tags. Use @user-create instead.
```

## Error Handling

### Orphan placeholder (production only)

```
  Error: @user-delete has no matching tests
    Production: App\UserService::delete
    Tests: (none)

  Resolution skipped. Add tests with @user-delete or remove the placeholder.
```

### Orphan placeholder (test only)

```
  Error: @orphan-test has no matching production code
    Production: (none)
    Tests: Tests\UserServiceTest::test_creates_user

  Resolution skipped. Add production code with @orphan-test or remove the placeholder.
```

## Exit Codes

| Code | Meaning |
|------|---------|
| `0` | Success (all resolved) |
| `1` | Error (orphan placeholders) |

## Workflow

### Recommended workflow

```bash
# 1. Check for unresolved placeholders
./vendor/bin/testlink validate

# 2. Preview resolution
./vendor/bin/testlink pair --dry-run

# 3. Apply resolution
./vendor/bin/testlink pair

# 4. Verify result
./vendor/bin/testlink validate
```

### During TDD/BDD

```bash
# After completing feature development
./vendor/bin/testlink pair

# Or resolve incrementally
./vendor/bin/testlink pair --placeholder=@current-feature
```

