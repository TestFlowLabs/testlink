# Auto-Sync Overview

Auto-sync helps keep your coverage links consistent across test files. It can add missing links and remove orphaned ones.

## What Sync Does

The sync command scans your test files for `#[LinksAndCovers]` / `#[Links]` attributes (PHPUnit) and `linksAndCovers()` / `links()` method chains (Pest), then helps synchronize them.

## Basic Workflow

### 1. Add Links to Tests

::: code-group

```php [Pest]
test('creates user', function () {
    // Test implementation
})->linksAndCovers(UserService::class.'::create');

test('validates email', function () {
    // Test implementation
})->linksAndCovers(UserService::class.'::create');
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_user(): void
    {
        // Test implementation
    }

    #[LinksAndCovers(UserService::class, 'create')]
    public function test_validates_email(): void
    {
        // Test implementation
    }
}
```

:::

### 2. Preview Changes

```bash
testlink sync --dry-run
```

### 3. Apply Changes

```bash
testlink sync
```

## Sync Options

| Option | Description |
|--------|-------------|
| `--dry-run` | Preview changes without applying |
| `--path=<dir>` | Limit to specific directory |
| `--link-only` | Use `links()` / `#[Links]` instead of `linksAndCovers()` / `#[LinksAndCovers]` |
| `--prune` | Remove orphaned link calls |
| `--force` | Required with --prune for safety |
| `--framework=<fw>` | Target framework (pest, phpunit, auto) |

## Idempotent Operations

Sync is idempotent - running it multiple times produces the same result:

- Existing links are preserved
- Only missing links are added
- No duplicates are created

## Error Handling

Sync will report errors for:

- Test files that don't exist
- Test cases that can't be found
- Parse errors in test files

These errors are reported but don't stop processing of other files.

