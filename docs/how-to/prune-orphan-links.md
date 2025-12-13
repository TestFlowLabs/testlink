# Prune Orphan Links

This guide shows how to identify and remove orphaned links from your codebase.

## What are Orphan Links?

Orphan links are references that point to non-existent targets:

| Type | Description |
|------|-------------|
| Orphan `#[TestedBy]` | Points to a test that doesn't exist |
| Orphan @see tag | Points to a method that doesn't exist |
| Orphan `linksAndCovers()` | Points to a method that was removed |

## Finding Orphan Links

### Run validation

```bash
./vendor/bin/testlink validate
```

Output:
```
  Validation Report
  ─────────────────

  ✗ Found 2 orphan TestedBy link(s):
    App\UserService::create
      → Tests\UserServiceTest::test_old_method (test not found)
    App\OrderService::process
      → Tests\OrderServiceTest::test_removed (test not found)

  ✗ Found 1 orphan @see tag(s):
    tests/UserServiceTest.php:15
      @see \App\Services\DeletedService::method (class not found)
```

## Removing Orphan @see Tags

### Using sync with prune

```bash
# Preview what will be removed
./vendor/bin/testlink sync --prune --dry-run

# Apply pruning
./vendor/bin/testlink sync --prune
```

Output:
```
  Syncing Coverage Links
  ──────────────────────

  Pruning orphan @see tags...

    tests/UserServiceTest.php
      - @see \App\Services\DeletedService::method (orphan)

    tests/OrderServiceTest.php
      - @see \App\OrderService::removedMethod (orphan)

  Removed 2 orphan @see tag(s) from 2 file(s).
```

### Manual removal

For orphan @see tags you want to remove manually:

```php
// Before
/**
 * @see \App\Services\DeletedService::method  // Remove this
 * @see \App\Services\UserService::create     // Keep this
 */
public function test_creates_user(): void

// After
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
```

## Removing Orphan #[TestedBy]

### Manual removal

`#[TestedBy]` attributes must be removed manually from production code:

```php
// Before
class UserService
{
    #[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
    #[TestedBy('Tests\UserServiceTest', 'test_old_method')]  // Remove this
    public function create(array $data): User
    {
        // ...
    }
}

// After
class UserService
{
    #[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
    public function create(array $data): User
    {
        // ...
    }
}
```

### Finding what to remove

Use verbose validation to see file locations:

```bash
./vendor/bin/testlink validate --verbose
```

Output:
```
  ✗ Orphan TestedBy: App\UserService::create
      File: src/Services/UserService.php
      Line: 15
      Target: Tests\UserServiceTest::test_old_method
      Status: Test not found
```

## Removing Orphan linksAndCovers()

### Manual removal from Pest

```php
// Before
test('creates user', function () {
    // ...
})
->linksAndCovers(UserService::class.'::create')
->linksAndCovers(DeletedService::class.'::method');  // Remove this

// After
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

### Manual removal from PHPUnit

```php
// Before
#[LinksAndCovers(UserService::class, 'create')]
#[LinksAndCovers(DeletedService::class, 'method')]  // Remove this
public function test_creates_user(): void

// After
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
```

## Preventing Orphan Links

### 1. Update links when refactoring

When you rename or delete methods:

```bash
# After refactoring
./vendor/bin/testlink validate

# Fix any issues found
./vendor/bin/testlink sync --prune
```

### 2. Use IDE refactoring

When using IDE "Rename Method":
- Update `#[TestedBy]` manually
- Run validation to catch misses

### 3. Run validation in CI

```yaml
- name: Check for orphans
  run: ./vendor/bin/testlink validate --strict
```

### 4. Regular cleanup

Schedule periodic cleanup:

```bash
# Weekly cleanup
./vendor/bin/testlink sync --prune
./vendor/bin/testlink validate
```

## Bulk Operations

### Find all orphans

```bash
./vendor/bin/testlink validate --json | jq '.orphans'
```

### Prune specific path

```bash
./vendor/bin/testlink sync --prune --path=tests/Unit
```

### Combined sync and prune

```bash
./vendor/bin/testlink sync --prune --force
```

## What Prune Removes

| Target | Removed by --prune |
|--------|-------------------|
| Orphan @see tags | ✓ Yes |
| Orphan #[TestedBy] | ✗ No (manual) |
| Orphan linksAndCovers() | ✗ No (manual) |
| Orphan #[LinksAndCovers] | ✗ No (manual) |

::: tip
Only @see tags are auto-pruned. Attributes and method chains require manual removal for safety.
:::
