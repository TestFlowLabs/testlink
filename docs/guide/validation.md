# Validation

Validation ensures your `#[TestedBy]` attributes and test links stay synchronized.

## Running Validation

```bash
testlink validate
```

Or via Pest:

```bash
pest --validate-coverage-links
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

## Validation Output

### Success

```
Scanning for #[TestedBy] attributes...

Found 12 production methods with #[TestedBy] attributes:
  UserService::create (2 tests)
  UserService::update (1 test)
  OrderService::place (3 tests)
  ...

Validation Results:
  ✓ All 15 links are synchronized

Coverage Summary:
  Methods with tests: 12
  Total test links: 15
  Orphaned links: 0
```

### Failure

```
Scanning for #[TestedBy] attributes...

Found 12 production methods with #[TestedBy] attributes

Validation Results:
  ✗ 3 synchronization issues found

Issues:
  1. Missing link for: App\Services\UserService::create
     Declared in: #[TestedBy(UserServiceTest::class, 'creates user')]
     Expected in: tests/Unit/UserServiceTest.php

  2. Test not found: App\Services\OrderService::place
     Declared in: #[TestedBy(OrderServiceTest::class, 'missing test')]

  3. Orphaned link in: tests/Unit/LegacyTest.php
     Method: linksAndCovers(OldService::class.'::removed')
     No matching #[TestedBy] attribute found

Exit code: 1
```

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
