# Sync Links Automatically

This guide shows how to use the `sync` command to automatically generate and maintain links between production code and tests.

## What Sync Does

The sync command:
1. Reads `#[TestedBy]` attributes from production code
2. Finds corresponding test files
3. Adds `@see` tags or method chains to tests
4. Optionally adds `@see` tags to production code

## Basic Usage

### Step 1: Run sync with dry-run

Always preview changes first:

```bash
./vendor/bin/testlink sync --dry-run
```

Output:
```
  Syncing Coverage Links
  ──────────────────────
  Running in dry-run mode. No files will be modified.


  Would add @see tags to
    ✓ UserService::create
      + @see UserServiceTest::test_creates_user
    ✓ OrderService::process
      + @see OrderServiceTest::test_processes_order

  Would add 2 @see tag(s).

    Run without --dry-run to apply changes:
    testlink sync
```

### Step 2: Apply changes

If the preview looks correct:

```bash
./vendor/bin/testlink sync
```

Output:
```
  Syncing Coverage Links
  ──────────────────────

  Modified Files
    ✓ tests/UserServiceTest.php (1 change)
    ✓ tests/OrderServiceTest.php (1 change)

  Sync complete. Modified 2 file(s).
```

## Sync Options

### Sync specific directory

```bash
./vendor/bin/testlink sync --path=src/Services
```

### Link-only mode

Add links to tests only (don't modify production code):

```bash
./vendor/bin/testlink sync --link-only
```

### With pruning

Remove orphaned @see tags:

```bash
./vendor/bin/testlink sync --prune
```

### Force overwrite

Replace existing links even if they differ:

```bash
./vendor/bin/testlink sync --force
```

## What Gets Synced

### From Production to Tests

When production code has:

```php
class UserService
{
    #[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
    public function create(array $data): User
    {
        // ...
    }
}
```

Sync adds to test:

:::tabs key:stack
== Pest

```php
/**
 * @see \App\UserService::create
 */
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

== PHPUnit + Attributes

```php
use TestFlowLabs\TestingAttributes\LinksAndCovers;

/**
 * @see \App\UserService::create
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
 * @see \App\UserService::create
 */
public function test_creates_user(): void
{
    // ...
}
```

:::

### From Tests to Production

When tests have links but production doesn't have `#[TestedBy]`:

```bash
./vendor/bin/testlink sync --link-only
```

Adds @see tags to production methods pointing back to tests.

## Sync Workflow

### Recommended workflow

```bash
# 1. Check current state
./vendor/bin/testlink validate

# 2. Preview sync changes
./vendor/bin/testlink sync --dry-run

# 3. Apply sync
./vendor/bin/testlink sync

# 4. Verify result
./vendor/bin/testlink validate
```

### After adding new tests

```bash
# 1. Write your test with linksAndCovers()
# 2. Sync to update production code
./vendor/bin/testlink sync --link-only

# 3. Validate
./vendor/bin/testlink validate
```

### After refactoring

```bash
# 1. Sync with prune to clean up
./vendor/bin/testlink sync --prune

# 2. Check for issues
./vendor/bin/testlink validate
```

## Combining Options

```bash
# Preview sync with pruning in specific directory
./vendor/bin/testlink sync --dry-run --prune --path=src/Services

# Force sync with link-only
./vendor/bin/testlink sync --force --link-only
```

## Sync Behavior

### What sync adds

| Source | Target | What's Added |
|--------|--------|--------------|
| `#[TestedBy]` on method | Test file | @see tag or method chain |
| `linksAndCovers()` in test | Production file | @see tag (with --link-only) |

### What sync doesn't do

- Doesn't remove `#[TestedBy]` attributes
- Doesn't modify test logic
- Doesn't create new test files
- Doesn't guess test names

## Handling Conflicts

### When sync finds existing links

By default, sync skips files that already have the link.

Use `--force` to overwrite:

```bash
./vendor/bin/testlink sync --force
```

### When method names don't match

If the test name in `#[TestedBy]` doesn't match the actual test:

```
Warning: Test 'test_old_name' not found in Tests\UserServiceTest
```

Fix the `#[TestedBy]` attribute first:

```php
// Wrong
#[TestedBy('Tests\UserServiceTest', 'test_old_name')]

// Correct
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
```

## Best Practices

1. **Always dry-run first** - Preview before modifying files
2. **Commit before sync** - Easy to revert if needed
3. **Run after refactoring** - Keep links up to date
4. **Use in CI** - Ensure links stay synchronized
5. **Prune regularly** - Remove stale links
