# sync

Synchronize coverage links between production code and tests.

## Synopsis

```bash
testlink sync [options]
```

## Description

The `sync` command:

1. Reads `#[TestedBy]` attributes from production code
2. Adds corresponding @see tags or method chains to test files
3. Optionally adds @see tags to production code from test links
4. Can prune orphaned @see tags

## Options

| Option | Description |
|--------|-------------|
| `--dry-run` | Preview changes without modifying files |
| `--prune` | Remove orphaned @see tags |
| `--link-only` | Only add links, don't modify production |
| `--force` | Overwrite existing links |
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


  Would add @see tags to
    ✓ UserService::create
      + @see UserServiceTest::test_creates_user
      + @see UserServiceTest::test_validates_email
    ✓ OrderService::process
      + @see OrderServiceTest::test_processes_order

  Would add 3 @see tag(s).

    Run without --dry-run to apply changes:
    testlink sync
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
    ✓ tests/Unit/OrderServiceTest.php (1 change)

  Sync complete. Modified 2 file(s).
```

### Sync with pruning

```bash
./vendor/bin/testlink sync --prune
```

Output:
```
  Syncing Coverage Links
  ──────────────────────

  Adding links
    ✓ tests/UserServiceTest.php (+1)

  Pruning orphans
    ✓ tests/OldTest.php (-2 orphan @see tags)

  Sync complete. Modified 2 file(s).
```

### Link-only mode

Only add links, don't modify production code:

```bash
./vendor/bin/testlink sync --link-only
```

### Force overwrite

Replace existing links even if different:

```bash
./vendor/bin/testlink sync --force
```

### Filter by path

```bash
./vendor/bin/testlink sync --path=src/Services
```

## What Gets Synced

### From #[TestedBy] to tests

Production:
```php
class UserService
{
    #[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
    public function create(): User { }
}
```

After sync, test file gets:
```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void { }
```

### From tests to production (with --link-only)

Test:
```php
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

After sync --link-only, production gets:
```php
/**
 * @see \Tests\UserServiceTest::test_creates_user
 */
public function create(): User { }
```

## Pruning Behavior

With `--prune`, removes @see tags that:
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
./vendor/bin/testlink sync --dry-run --prune

# Apply
./vendor/bin/testlink sync --prune
```

