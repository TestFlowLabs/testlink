# Fix Validation Errors

This guide shows how to identify and fix common TestLink validation errors.

## Running Validation

```bash
./vendor/bin/testlink validate
```

## Common Errors and Solutions

### 1. Orphan TestedBy

**Error:**
```
  ✗ Found 1 orphan TestedBy link(s):
    App\UserService::create
      → Tests\UserServiceTest::test_creates_user (test not found)
```

**Cause:** A `#[TestedBy]` attribute exists in production code, but the test doesn't have a corresponding test link (`->linksAndCovers()` in Pest, `#[LinksAndCovers]` in PHPUnit, or `@see` tag).

**Solutions:**

Option A: Add the missing link in the test:

:::tabs key:stack
== Pest

```php
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

== PHPUnit + Attributes

```php
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
{
    // ...
}
```

== PHPUnit + @see

```php
/**
 * @see \App\UserService::create
 */
public function test_creates_user(): void
{
    // ...
}
```

:::

Option B: Remove the `#[TestedBy]` if the test was removed:

```php
// Remove this line if test no longer exists
// #[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
public function create(array $data): User
```

Option C: Run sync to auto-fix:

```bash
./vendor/bin/testlink sync
```

### 2. Missing TestedBy

**Error:**
```
  ⚠ Found 1 test link(s) without TestedBy:
    Tests\UserServiceTest::test_creates_user
      → App\UserService::create (no TestedBy attribute)
```

**Cause:** A test has a link (`->linksAndCovers()`, `#[LinksAndCovers]`, or `@see`), but the production method doesn't have `#[TestedBy]`.

**Solutions:**

Option A: Add the missing `#[TestedBy]`:

```php
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
public function create(array $data): User
```

Option B: Run sync with link-only mode:

```bash
./vendor/bin/testlink sync --link-only
```

### 3. Duplicate Links

**Error:**
```
  ✗ Found 1 duplicate link(s):
    Tests\UserServiceTest::test_creates_user
      → App\UserService::create
      Found in: PHPUnit attribute, Pest method chain
```

**Cause:** The same link is declared in both a PHPUnit attribute AND a Pest method chain.

**Solution:** Remove one of the duplicates. Keep only:

```php
// Either use the attribute
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void

// OR use the method chain
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

### 4. Unresolved Placeholder

**Error:**
```
  ✗ Found 1 unresolved placeholder(s):
    @user-create
      Production: App\UserService::create
      Tests: Tests\UserServiceTest::test_creates_user
```

**Cause:** A placeholder marker hasn't been resolved to real references.

**Solution:** Run the pair command:

```bash
# Preview first
./vendor/bin/testlink pair --dry-run

# Apply changes
./vendor/bin/testlink pair
```

### 5. Invalid Test Reference

**Error:**
```
  ✗ Found 1 invalid TestedBy reference(s):
    App\UserService::create
      → Tests\UserServiceTest::test_nonexistent (test not found)
```

**Cause:** The `#[TestedBy]` points to a test that doesn't exist.

**Solutions:**

Option A: Fix the test name:

```php
// Wrong
#[TestedBy('Tests\UserServiceTest', 'test_nonexistent')]

// Correct
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
```

Option B: Remove if test was deleted:

```php
// Remove the stale TestedBy
public function create(array $data): User
```

### 6. Invalid Method Reference

**Error:**
```
  ✗ Found 1 invalid linksAndCovers reference(s):
    Tests\UserServiceTest::test_creates_user
      → App\UserService::nonexistent (method not found)
```

**Cause:** The test link points to a method that doesn't exist.

**Solution:** Fix the method reference:

```php
// Wrong
->linksAndCovers(UserService::class.'::nonexistent')

// Correct
->linksAndCovers(UserService::class.'::create')
```

### 7. FQCN Issue in @see Tag

**Error:**
```
  ⚠ Found 1 @see tag FQCN issue(s):
    tests/UserServiceTest.php:15
      @see UserService::create (should use FQCN: \App\UserService)
```

**Cause:** An @see tag uses a short class name instead of fully qualified.

**Solution:** Use the full class name:

```php
// Wrong
/** @see UserService::create */

// Correct
/** @see \App\UserService::create */

// Or with use statement
use App\UserService;
/** @see UserService::create */
```

## Batch Fixing

### Fix all sync issues

```bash
./vendor/bin/testlink sync
```

### Fix all placeholder issues

```bash
./vendor/bin/testlink pair
```

### Fix orphaned @see tags

```bash
./vendor/bin/testlink sync --prune
```

## Dry-Run First

Always preview changes before applying:

```bash
# Preview sync changes
./vendor/bin/testlink sync --dry-run

# Preview pair changes
./vendor/bin/testlink pair --dry-run
```

## Verbose Output

For more details about errors:

```bash
./vendor/bin/testlink validate --verbose
```

This shows:
- File paths
- Line numbers
- Additional context

## Suppressing Warnings

If you want to ignore certain warnings temporarily:

```bash
# Only fail on errors, not warnings
./vendor/bin/testlink validate

# Fail on both errors and warnings
./vendor/bin/testlink validate --strict
```

## Common Causes of Errors

| Error | Common Cause |
|-------|--------------|
| Orphan TestedBy | Test was renamed or deleted |
| Missing TestedBy | New test added without updating production |
| Duplicate links | Mixed PHPUnit attributes with Pest chains |
| Unresolved placeholder | Forgot to run `pair` before committing |
| Invalid reference | Typo in class or method name |
