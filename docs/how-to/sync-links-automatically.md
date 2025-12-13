# Sync Links Automatically

This guide shows how to use the `sync` command to automatically generate and maintain bidirectional links between production code and tests.

## What Sync Does

The sync command maintains bidirectional links:

**Forward sync (Production → Test):**
1. Reads `#[TestedBy]` attributes from production code
2. Adds `->linksAndCovers()` (Pest) or `#[LinksAndCovers]` (PHPUnit) to tests
3. Adds `@see` tags to production for IDE navigation

**Reverse sync (Test → Production):**
1. Reads `->linksAndCovers()` or `#[LinksAndCovers]` from tests
2. Adds `#[TestedBy]` attributes to production methods

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

  Would modify test files
    ✓ tests/Unit/UserServiceTest.php
      + linksAndCovers(UserService::class.'::create')

  Would add #[TestedBy] to production
    ✓ OrderService::process
      + #[TestedBy(OrderServiceTest::class, 'test_processes_order')]

  Dry run complete. Would modify 2 file(s).
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
    ✓ src/Services/OrderService.php (1 change)

  Sync complete. Modified 2 file(s).
```

## Sync Options

### Sync specific directory

```bash
./vendor/bin/testlink sync --path=src/Services
```

### Link-only mode

Add test-side links without coverage (uses `->links()` instead of `->linksAndCovers()`):

```bash
./vendor/bin/testlink sync --link-only
```

### With pruning

Remove orphaned links:

```bash
./vendor/bin/testlink sync --prune --force
```

## What Gets Synced

### Forward: Production to Tests

When production code has:

```php
class UserService
{
    #[TestedBy(UserServiceTest::class, 'test_creates_user')]
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
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

== PHPUnit + Attributes

```php
use TestFlowLabs\TestingAttributes\LinksAndCovers;

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

### Reverse: Tests to Production

When test has a link but production doesn't have `#[TestedBy]`:

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

Sync adds `#[TestedBy]` to production:

```php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'test_creates_user')]
    public function create(array $data): User
    {
        // ...
    }
}
```

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

### Start from Production

Add `#[TestedBy]` to production, then sync:

```bash
# 1. Add #[TestedBy] to production method
# 2. Sync to add link to test
./vendor/bin/testlink sync
```

### Start from Tests

Add `linksAndCovers()` to test, then sync:

```bash
# 1. Write test with linksAndCovers()
# 2. Sync to add #[TestedBy] to production
./vendor/bin/testlink sync
```

### After refactoring

```bash
# 1. Sync with prune to clean up
./vendor/bin/testlink sync --prune --force

# 2. Check for issues
./vendor/bin/testlink validate
```

## Bidirectional Sync Explained

| You add link here | sync adds to |
|-------------------|--------------|
| Production: `#[TestedBy(...)]` | Test: `linksAndCovers()` or `#[LinksAndCovers]` |
| Test: `linksAndCovers()` or `#[LinksAndCovers]` | Production: `#[TestedBy(...)]` |

Both sides also get `@see` tags for Cmd+Click navigation.

## Combining Options

```bash
# Preview sync with pruning in specific directory
./vendor/bin/testlink sync --dry-run --prune --force --path=src/Services

# Sync with link-only (no coverage)
./vendor/bin/testlink sync --link-only
```

## Sync Behavior

### What sync adds

| Source | Target | What's Added |
|--------|--------|--------------|
| `#[TestedBy]` on production | Test file | `linksAndCovers()` or `#[LinksAndCovers]` |
| `linksAndCovers()`/`#[LinksAndCovers]` in test | Production file | `#[TestedBy]` attribute |
| All links | Both sides | `@see` tags for navigation |

### What sync doesn't do

- Doesn't remove `#[TestedBy]` attributes (use `--prune` for cleanup)
- Doesn't modify test logic
- Doesn't create new test files
- Doesn't guess test names

## Handling Conflicts

### When sync finds existing links

By default, sync skips links that already exist.

### When method names don't match

If the test name in `#[TestedBy]` doesn't match the actual test:

```
Warning: Test 'test_old_name' not found in Tests\UserServiceTest
```

Fix the `#[TestedBy]` attribute first:

```php
// Wrong
#[TestedBy(UserServiceTest::class, 'test_old_name')]

// Correct
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
```

## Best Practices

1. **Always dry-run first** - Preview before modifying files
2. **Commit before sync** - Easy to revert if needed
3. **Run after refactoring** - Keep links up to date
4. **Use in CI** - Ensure links stay synchronized
5. **Prune regularly** - Remove stale links with `--prune --force`
