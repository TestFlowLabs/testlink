# Validation

Validation ensures your `#[TestedBy]` attributes and test links stay synchronized.

## Running Validation

```bash
testlink validate
```

## What Gets Validated

### 1. TestedBy → Test Links

Every `#[TestedBy]` attribute must have a matching link in the test:

::: code-group

```php [Pest]
// Production code
#[TestedBy(UserTest::class, 'creates user')]
public function create() { }

// Test code - must exist and have linksAndCovers()
test('creates user', function () {
    // ...
})->linksAndCovers(User::class.'::create');  // ✓ Required
```

```php [PHPUnit]
// Production code
#[TestedBy(UserTest::class, 'test_creates_user')]
public function create() { }

// Test code - must exist and have #[LinksAndCovers]
#[LinksAndCovers(User::class, 'create')]  // ✓ Required
public function test_creates_user(): void
{
    // ...
}
```

:::

### 2. Test Existence

The test referenced by `#[TestedBy]` must exist:

```php
// This will fail validation if the test doesn't exist
#[TestedBy(UserTest::class, 'nonexistent test')]
public function create() { }
```

### 3. Method Reference Match

The link must reference the correct method:

::: code-group

```php [Pest]
#[TestedBy(UserTest::class, 'creates user')]
public function create() { }

// Wrong - references different method
test('creates user', function () { })
    ->linksAndCovers(User::class.'::update');  // ✗ Mismatch
```

```php [PHPUnit]
#[TestedBy(UserTest::class, 'test_creates_user')]
public function create() { }

// Wrong - references different method
#[LinksAndCovers(User::class, 'update')]  // ✗ Mismatch
public function test_creates_user(): void { }
```

:::

### 4. @see Tag Validation

@see tags in docblocks are scanned for validity:

::: code-group

```php [Production File]
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
public function create(): User
{
    // Implementation
}
```

```php [Test File (PHPUnit)]
/**
 * @see \App\Services\UserService::create
 */
#[Test]
public function test_creates_user(): void
{
    // Test implementation
}
```

:::

**What Gets Checked:**

- @see tags pointing to non-existent classes
- @see tags pointing to non-existent methods
- @see tags pointing to deleted/renamed tests

::: tip @see Tags
@see tags provide full IDE method navigation. See [@see Tags Guide](/guide/see-tags) for usage details.
:::

## Validation Output

### Success

```
  Validation Report
  ─────────────────

  Link Summary
  ────────────

    PHPUnit attribute links: 5
    Pest method chain links: 10
    Total links: 15

  ✓ All links are valid!
```

### With Unresolved Placeholders

```
  Validation Report
  ─────────────────

  Unresolved Placeholders
  ───────────────────────

    ⚠ @user-create  (1 production, 2 tests)
    ⚠ @A  (2 production, 0 tests)

    ⚠ Run "testlink pair" to resolve placeholders.

  Link Summary
  ────────────

    PHPUnit attribute links: 5
    Pest method chain links: 10
    Total links: 15

  ✓ All links are valid!
```

### Failure (Duplicate Links)

```
  Validation Report
  ─────────────────

  Duplicate Links Found
  ─────────────────────

    ! Tests\Unit\UserServiceTest::test_creates_user
      → App\Services\UserService::create

  ⚠ Consider using only one linking method per test.
```

### With Orphan @see Tags

```
  Validation Report
  ─────────────────

  Orphan @see Tags
  ────────────────

    ⚠ @see \Tests\Unit\OldTest::deleted_test
      in src/Services/UserService.php:45

    ⚠ @see \App\Services\RemovedService::method
      in tests/Unit/UserServiceTest.php:23

  Link Summary
  ────────────

    PHPUnit attribute links: 5
    @see tags: 4 (2 orphans)
    Total links: 5
```

::: warning Fixing Orphan @see Tags
Use `testlink sync --prune --force` to automatically remove orphan @see tags.
:::

## Fixing Validation Errors

### Missing Link

Add the appropriate link to the test:

::: code-group

```php [Pest]
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

```php [PHPUnit]
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
{
    // ...
}
```

:::

Or use auto-sync:

```bash
testlink sync
```

### Test Not Found

Either:
1. Create the missing test
2. Remove the `#[TestedBy]` attribute
3. Fix the test name in the attribute

### Orphaned Link

Either:
1. Add a `#[TestedBy]` attribute to the production method
2. Remove the link from the test
3. Use pruning: `testlink sync --prune --force`

## CI Integration

Add validation to your CI pipeline:

```yaml
# GitHub Actions
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install Dependencies
        run: composer install

      - name: Validate Coverage Links
        run: ./vendor/bin/testlink validate

      - name: Run Tests
        run: ./vendor/bin/pest  # or ./vendor/bin/phpunit
```

## Selective Validation

Limit validation to specific directories:

```bash
testlink validate --path=src/Services
```

## Best Practices

1. **Run validation in CI** - Catch issues before merging
2. **Validate before releases** - Ensure documentation is accurate
3. **Use auto-sync** - Keep links updated automatically
4. **Fix issues immediately** - Don't let validation debt accumulate
