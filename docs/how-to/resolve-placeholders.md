# Resolve Placeholders

This guide shows how to resolve placeholder markers into real class and method references.

## What are Placeholders?

Placeholders are temporary markers used during development:

```php
// Production code
#[TestedBy('@user-create')]

// Test code
->linksAndCovers('@user-create')
```

They let you defer naming decisions until you're ready.

## Basic Resolution

### Step 1: Preview the resolution

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
    ✓ @user-create   1 production × 2 tests = 2 links


  Production Files
    src/Services/UserService.php
      @user-create → UserServiceTest::test_creates_user
      @user-create → UserServiceTest::test_validates_user


  Test Files
    tests/UserServiceTest.php
      @user-create → UserService::create

  Dry run complete. Would modify 2 file(s) with 3 change(s).
```

### Step 2: Apply the resolution

```bash
./vendor/bin/testlink pair
```

### Step 3: Verify

```bash
./vendor/bin/testlink validate
```

## Resolving Specific Placeholders

### Single placeholder

```bash
./vendor/bin/testlink pair --placeholder=@user-create
```

### Multiple placeholders

Run the command multiple times or resolve all at once:

```bash
# All placeholders
./vendor/bin/testlink pair

# Or specific ones
./vendor/bin/testlink pair --placeholder=@user-create
./vendor/bin/testlink pair --placeholder=@order-flow
```

## Understanding N:M Resolution

Placeholders create N:M relationships:

```
1 production method with @A + 3 tests with @A = 3 links
2 production methods with @B + 2 tests with @B = 4 links
```

### Example

**Before resolution:**

```php
// Production: 2 methods use @checkout
class OrderService
{
    #[TestedBy('@checkout')]
    public function validate(): bool { }

    #[TestedBy('@checkout')]
    public function process(): Order { }
}

// Tests: 3 tests use @checkout
test('validates order data')
    ->linksAndCovers('@checkout');

test('processes valid order')
    ->linksAndCovers('@checkout');

test('sends confirmation')
    ->linksAndCovers('@checkout');
```

**After resolution (2 × 3 = 6 links):**

```php
// Production
class OrderService
{
    #[TestedBy('Tests\OrderServiceTest', 'validates order data')]
    #[TestedBy('Tests\OrderServiceTest', 'processes valid order')]
    #[TestedBy('Tests\OrderServiceTest', 'sends confirmation')]
    public function validate(): bool { }

    #[TestedBy('Tests\OrderServiceTest', 'validates order data')]
    #[TestedBy('Tests\OrderServiceTest', 'processes valid order')]
    #[TestedBy('Tests\OrderServiceTest', 'sends confirmation')]
    public function process(): Order { }
}

// Tests
test('validates order data')
    ->linksAndCovers(OrderService::class.'::validate')
    ->linksAndCovers(OrderService::class.'::process');

test('processes valid order')
    ->linksAndCovers(OrderService::class.'::validate')
    ->linksAndCovers(OrderService::class.'::process');

test('sends confirmation')
    ->linksAndCovers(OrderService::class.'::validate')
    ->linksAndCovers(OrderService::class.'::process');
```

## Resolving by Path

Resolve placeholders in specific directories:

```bash
./vendor/bin/testlink pair --path=src/Services
```

## Handling Orphan Placeholders

### What are orphan placeholders?

Placeholders that exist only on one side:

```
Warning: @user-delete has no matching tests
Warning: @orphan-test has no matching production code
```

### How to handle them

1. **Add matching code** - Create the missing production/test
2. **Remove the placeholder** - If it's no longer needed
3. **Use different placeholder** - If you meant a different one

### Finding orphans

```bash
./vendor/bin/testlink validate
```

Look for:
```
  ✗ Found unresolved placeholder(s):
    @user-delete
      Production: App\UserService::delete
      Tests: (none)
```

## Resolution in Different Frameworks

### Pest resolution

Before:
```php
test('creates user', function () {
    // ...
})->linksAndCovers('@user-create');
```

After:
```php
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

### PHPUnit resolution

Before:
```php
#[LinksAndCovers('@user-create')]
public function test_creates_user(): void
```

After:
```php
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
```

## Using `@@` Prefix for `@see` Tags

Instead of attributes, you can use the `@@` prefix to generate `@see` tags in PHPDoc comments.

::: warning PHPUnit Only
The `@@` prefix only works with PHPUnit. Pest tests do not support `@see` tags.
:::

### When to use `@@`

- You prefer documentation-style links
- Your team uses `@see` tags for traceability
- You want FQCN format in docblocks

### Example

Before:
```php
// Production
#[TestedBy('@@user-create')]
public function create(): User { }

// Test (PHPUnit)
#[LinksAndCovers('@@user-create')]
public function testCreatesUser(): void { }
```

After:
```php
// Production
/** @see \Tests\Unit\UserServiceTest::testCreatesUser */
public function create(): User { }

// Test
/** @see \App\Services\UserService::create */
public function testCreatesUser(): void { }
```

### Error with Pest

Using `@@` with Pest tests results in an error:

```
Error: Placeholder @@user-create uses @@prefix (for @see tags) but Pest tests
do not support @see tags. Use @user-create instead.
```

Switch to `@user-create` (single `@`) to use attributes instead.

## Best Practices

### 1. Resolve before committing

```bash
# Check for unresolved placeholders
./vendor/bin/testlink validate

# If any found, resolve them
./vendor/bin/testlink pair
```

### 2. Use in CI to block unresolved

```yaml
- name: Check for placeholders
  run: |
    if ./vendor/bin/testlink validate 2>&1 | grep -q "unresolved placeholder"; then
      echo "Found unresolved placeholders!"
      exit 1
    fi
```

### 3. Preview complex resolutions

For placeholders with many matches:

```bash
./vendor/bin/testlink pair --dry-run --placeholder=@complex-feature
```

Review the N:M expansion before applying.

### 4. Resolve incrementally

Don't let placeholders pile up:

```bash
# After each feature is complete
./vendor/bin/testlink pair
```
