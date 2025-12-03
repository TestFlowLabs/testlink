# Dry Run Mode

Dry run mode previews sync changes without modifying any files. Always use dry run first to verify what will change.

## Usage

```bash
testlink sync --dry-run
```

Or via Pest:

```bash
pest --sync-coverage-links --dry-run
```

## Output Format

```
Scanning production code for #[TestedBy] attributes...

Found 3 attribute(s) to sync:

  App\Services\UserService::create
  └── #[TestedBy(UserServiceTest::class, 'creates a new user')]
      → Will add link to test
      → File: tests/Unit/UserServiceTest.php
      → Test: "creates a new user"

  App\Services\UserService::create
  └── #[TestedBy(UserServiceTest::class, 'validates user data')]
      → Will add link to test
      → File: tests/Unit/UserServiceTest.php
      → Test: "validates user data"

  App\Services\OrderService::place
  └── #[TestedBy(OrderServiceTest::class, 'places order')]
      → Will add link to test
      → File: tests/Unit/OrderServiceTest.php
      → Test: "places order"

Run without --dry-run to apply changes.
```

## Understanding the Output

### Method Identifier

```
App\Services\UserService::create
```

The production method that has the `#[TestedBy]` attribute.

### Attribute Display

```
└── #[TestedBy(UserServiceTest::class, 'creates a new user')]
```

Shows the exact attribute that will be synced.

### What Will Be Added

For Pest tests:
```
→ Will add: ->linksAndCovers(UserService::class.'::create')
```

For PHPUnit tests:
```
→ Will add: #[LinksAndCovers(UserService::class, 'create')]
```

### Target Location

```
→ File: tests/Unit/UserServiceTest.php
→ Test: "creates a new user"
```

Which file and test case will be modified.

## No Changes Needed

When everything is already in sync:

```
Scanning production code for #[TestedBy] attributes...

No changes needed - all coverage links are already in sync.
```

## With Errors

Dry run also shows errors that would occur:

```
Scanning production code for #[TestedBy] attributes...

Found 2 attribute(s) to sync:

  App\Services\UserService::create
  └── #[TestedBy(UserServiceTest::class, 'creates user')]
      → Will add link to test
      → File: tests/Unit/UserServiceTest.php
      → Test: "creates user"

Errors:
  ✗ Test not found: "missing test" in tests/Unit/UserServiceTest.php
  ✗ File not found: tests/Unit/NonExistentTest.php
```

## Workflow

### 1. Run Dry Run

```bash
testlink sync --dry-run
```

### 2. Review Changes

- Check the files that will be modified
- Verify the test names are correct
- Ensure no unexpected changes

### 3. Apply If Satisfied

```bash
testlink sync
```

### 4. Verify

```bash
testlink validate
```

## Combining with Other Options

### Dry Run with Path Filter

```bash
testlink sync --dry-run --path=src/Services
```

Preview changes only for attributes in `src/Services`.

### Dry Run with Prune

```bash
testlink sync --dry-run --prune --force
```

Preview both additions and removals.

### Dry Run with Link Only

```bash
testlink sync --dry-run --link-only
```

Preview using `links()` / `#[Links]` instead of `linksAndCovers()` / `#[LinksAndCovers]`.

### Dry Run with Framework Filter

```bash
testlink sync --dry-run --framework=phpunit
```

Preview changes only for PHPUnit tests.

## Best Practices

1. **Always dry-run first** - Especially after major refactoring
2. **Review carefully** - Check that test names match exactly
3. **Use git** - Have a clean working tree to easily diff changes
4. **Run in CI** - Add dry-run checks to pull requests

## CI Integration

Add dry-run as a check in pull requests:

```yaml
- name: Check Sync Status
  run: |
    ./vendor/bin/testlink sync --dry-run 2>&1 | tee sync-output.txt
    if grep -q "Found.*attribute(s) to sync" sync-output.txt; then
      echo "::warning::Coverage links need syncing"
    fi
```
