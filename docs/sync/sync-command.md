# Sync Command

The `testlink sync` command synchronizes `#[TestedBy]` attributes to test links.

## Basic Usage

```bash
testlink sync
```

Or via Pest:

```bash
pest --sync-coverage-links
```

## How It Works

1. **Scans production code** - Finds all `#[TestedBy]` attributes via Composer classmap
2. **Locates test files** - Maps test class names to file paths
3. **Parses test files** - Finds test cases using PHP-Parser
4. **Injects links**:
   - **Pest**: Adds `->linksAndCovers()` calls before the semicolon
   - **PHPUnit**: Adds `#[LinksAndCovers]` attributes above the method

## Command Output

### Normal Mode

```bash
$ testlink sync

Scanning production code for #[TestedBy] attributes...

✓ Sync complete!

  3 link(s) added

Modified files:
  - tests/Unit/UserServiceTest.php (2 links added)
  - tests/Unit/OrderServiceTest.php (1 link added)
```

### No Changes Needed

```bash
$ testlink sync

Scanning production code for #[TestedBy] attributes...

✓ All coverage links are already in sync.
```

### With Errors

```bash
$ testlink sync

Scanning production code for #[TestedBy] attributes...

Sync failed with errors:

  ✗ Test not found: "nonexistent test" in tests/Unit/UserServiceTest.php
  ✗ File not found: tests/Unit/MissingTest.php
```

## Options Reference

### --dry-run

Preview changes without modifying files:

```bash
testlink sync --dry-run
```

See [Dry Run Mode](/sync/dry-run) for details.

### --path

Limit sync to a specific source directory:

```bash
testlink sync --path=src/Services
```

Only `#[TestedBy]` attributes in `src/Services` will be processed.

### --link-only

Use `links()` / `#[Links]` instead of `linksAndCovers()` / `#[LinksAndCovers]`:

```bash
testlink sync --link-only
```

Useful for integration/e2e tests where coverage is tracked separately.

### --prune

Remove orphaned link calls:

```bash
testlink sync --prune --force
```

See [Pruning Orphans](/sync/pruning) for details.

### --force

Required when using `--prune` as a safety measure:

```bash
# This will fail
testlink sync --prune

# This works
testlink sync --prune --force
```

### --framework

Target a specific framework:

```bash
# Sync only Pest tests
testlink sync --framework=pest

# Sync only PHPUnit tests
testlink sync --framework=phpunit

# Auto-detect (default)
testlink sync --framework=auto
```

## Generated Code Format

### Pest Format

Input:
```php
#[TestedBy(UserServiceTest::class, 'creates user')]
```

Output:
```php
->linksAndCovers(UserService::class.'::create')
```

The format uses:
- Class constant (`::class`) for IDE support
- String concatenation for the method name
- Single quotes for consistency

### PHPUnit Format

Input:
```php
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
```

Output:
```php
#[LinksAndCovers(UserService::class, 'create')]
```

## Placement

::: code-group

```php [Pest - Before]
test('creates user', function () {
    // ...
});
```

```php [Pest - After]
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

```php [PHPUnit - Before]
public function test_creates_user(): void
{
    // ...
}
```

```php [PHPUnit - After]
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
{
    // ...
}
```

:::

## Multiple Methods

::: code-group

```php [Pest]
test('checkout flow', function () {
    // ...
})->linksAndCovers(CartService::class.'::checkout')
  ->linksAndCovers(PaymentService::class.'::charge');
```

```php [PHPUnit]
#[LinksAndCovers(CartService::class, 'checkout')]
#[LinksAndCovers(PaymentService::class, 'charge')]
public function test_checkout_flow(): void
{
    // ...
}
```

:::

## Error Handling

### Test File Not Found

```
Error: File not found: tests/Unit/MissingTest.php
```

The test class referenced in `#[TestedBy]` doesn't have a corresponding file.

### Test Case Not Found

```
Error: Test case not found: "missing test" in tests/Unit/UserServiceTest.php
```

The test name in `#[TestedBy]` doesn't match any test in the file.

### Parse Errors

```
Error: Could not parse: tests/Unit/BrokenTest.php
```

The test file has syntax errors that prevent parsing.

## Best Practices

1. **Use dry-run first** - Always preview before applying changes
2. **Commit before sync** - Have a clean git state to review changes
3. **Run validation after** - Ensure sync was successful
4. **Automate in CI** - Consider running sync in pull request checks

## Integration with Validation

After syncing, run validation to confirm everything is aligned:

```bash
testlink sync && testlink validate
```
