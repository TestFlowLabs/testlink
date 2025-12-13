# sync

Synchronize coverage links bidirectionally between production code and tests.

## Synopsis

```bash
testlink sync [options]
```

## Description

The `sync` command maintains bidirectional links between production and test code:

**Production → Test (forward sync):**
- Reads `#[TestedBy]` attributes from production code
- Adds `->linksAndCovers()` (Pest) or `#[LinksAndCovers]` (PHPUnit) to test files
- Adds `@see` tags to production code for IDE navigation

**Test → Production (reverse sync):**
- Reads `->linksAndCovers()` or `#[LinksAndCovers]` from test files
- Adds `#[TestedBy]` attributes to production methods

This ensures both directions stay synchronized regardless of which side you add links first.

## Options

| Option | Description |
|--------|-------------|
| `--dry-run` | Preview changes without modifying files |
| `--prune` | Remove orphaned links |
| `--link-only` | Add links without coverage (uses `->links()` instead of `->linksAndCovers()`) |
| `--force` | Required with `--prune` to confirm destructive operation |
| `--verbose`, `-v` | Show detailed information |
| `--path=<path>` | Filter by directory or file path |

## Examples

### Preview sync

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

  Would add @see tags to production
    ✓ UserService::create
      + @see UserServiceTest::test_creates_user

  Would add #[TestedBy] to production
    ✓ OrderService::process
      + #[TestedBy(OrderServiceTest::class, 'test_processes_order')]

  Dry run complete. Would modify 3 file(s).
```

### Apply sync

```bash
./vendor/bin/testlink sync
```

Output:
```
  Syncing Coverage Links
  ──────────────────────

  Modified Files
    ✓ tests/Unit/UserServiceTest.php (2 changes)
    ✓ src/Services/OrderService.php (1 change)

  Sync complete. Modified 2 file(s).
```

### Sync with pruning

```bash
./vendor/bin/testlink sync --prune --force
```

Output:
```
  Syncing Coverage Links
  ──────────────────────

  Adding links
    ✓ tests/UserServiceTest.php (+1)

  Pruning orphans
    ✓ tests/OldTest.php (-2 orphan links)

  Sync complete. Modified 2 file(s).
```

### Filter by path

```bash
./vendor/bin/testlink sync --path=src/Services
```

## What Gets Synced

### Forward Sync: Production → Test

When production has `#[TestedBy]`:
```php
class UserService
{
    #[TestedBy(UserServiceTest::class, 'test_creates_user')]
    public function create(): User { }
}
```

After sync, test file gets:

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
public function test_creates_user(): void { }
```
== PHPUnit + @see
```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void { }
```
:::

### Reverse Sync: Test → Production

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
public function test_creates_user(): void { }
```
== PHPUnit + @see
```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void { }
```
:::

After sync, production gets:
```php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'test_creates_user')]
    public function create(): User { }
}
```

### @see Tags for IDE Navigation

Production methods always get `@see` tags for Cmd+Click navigation:
```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User { }
```

## Pruning Behavior

With `--prune --force`, removes links that:
- Point to non-existent classes
- Point to non-existent methods
- Point to removed tests

Example:
```php
/**
 * @see \App\DeletedService::method    ← Removed (class doesn't exist)
 * @see \App\UserService::create       ← Kept
 */
```

## Exit Codes

| Code | Meaning |
|------|---------|
| `0` | Success |
| `1` | Error (unable to sync) |

## Workflow

### Recommended sync workflow

```bash
# 1. Preview changes
./vendor/bin/testlink sync --dry-run

# 2. Apply changes
./vendor/bin/testlink sync

# 3. Validate result
./vendor/bin/testlink validate
```

### With pruning

```bash
# Preview sync and prune
./vendor/bin/testlink sync --dry-run --prune --force

# Apply
./vendor/bin/testlink sync --prune --force
```

## Bidirectional Sync Explained

The sync command is truly bidirectional:

| You add link here | sync adds to |
|-------------------|--------------|
| Production: `#[TestedBy(...)]` | Test: `linksAndCovers()` or `#[LinksAndCovers]` |
| Test: `linksAndCovers()` or `#[LinksAndCovers]` | Production: `#[TestedBy(...)]` |

Both sides also get `@see` tags for IDE navigation.

This means you can start linking from either side:
1. Add `#[TestedBy]` to production, run `sync` → test gets link
2. Add `linksAndCovers()` to test, run `sync` → production gets `#[TestedBy]`
