# Use Dry-Run Mode

This guide explains how to use dry-run mode to preview changes before applying them.

## What is Dry-Run Mode?

Dry-run mode shows what a command would do without actually making changes. It's essential for:

- Previewing modifications before committing
- Understanding what will change
- Avoiding unintended modifications
- Reviewing changes in code review

## Available in These Commands

| Command | Dry-Run Flag |
|---------|--------------|
| `testlink sync` | `--dry-run` |
| `testlink pair` | `--dry-run` |

## Using Dry-Run with Sync

### Basic preview

```bash
./vendor/bin/testlink sync --dry-run
```

### Output example

```
  Syncing Coverage Links
  ──────────────────────
  Running in dry-run mode. No files will be modified.


  Would add @see tags to
    ✓ UserService::create
      + @see UserServiceTest::test_creates_user
      + @see UserServiceTest::test_updates_user
    ✓ OrderService::process
      + @see OrderServiceTest::test_processes_order

  Would add 3 @see tag(s).

    Run without --dry-run to apply changes:
    testlink sync
```

### With additional options

```bash
# Dry-run with pruning preview
./vendor/bin/testlink sync --dry-run --prune

# Dry-run for specific path
./vendor/bin/testlink sync --dry-run --path=src/Services
```

## Using Dry-Run with Pair

### Basic preview

```bash
./vendor/bin/testlink pair --dry-run
```

### Output example

```
  Pairing Placeholders
  ────────────────────
  Running in dry-run mode. No files will be modified.

  Scanning for placeholders...


  Found Placeholders
    ✓ @user-create   1 production × 2 tests = 2 links
    ✓ @order-flow    2 production × 3 tests = 6 links


  Production Files
    src/Services/UserService.php
      @user-create → UserServiceTest::test_creates_user
      @user-create → UserServiceTest::test_validates_email
    src/Services/OrderService.php
      @order-flow → OrderServiceTest::test_creates_order
      @order-flow → OrderServiceTest::test_processes_payment


  Test Files
    tests/Unit/UserServiceTest.php
      @user-create → UserService::create
    tests/Unit/OrderServiceTest.php
      @order-flow → OrderService::create
      @order-flow → OrderService::process

  Dry run complete. Would modify 4 file(s) with 8 change(s).

    Run without --dry-run to apply changes:
    testlink pair
```

### Preview specific placeholder

```bash
./vendor/bin/testlink pair --dry-run --placeholder=@user-create
```

## Understanding the Output

### File changes

```
    ✓ UserService::create
      + @see UserServiceTest::test_creates_user
           ↑
           What will be added
```

### Summary

```
  Would add 3 @see tag(s).
                       ↑
                       Individual changes
```

## Workflow with Dry-Run

### Step 1: Always start with dry-run

```bash
./vendor/bin/testlink sync --dry-run
```

### Step 2: Review the output

- Check that the right files are being modified
- Verify the changes make sense
- Look for unexpected modifications

### Step 3: Apply if satisfied

```bash
./vendor/bin/testlink sync
```

### Step 4: Verify result

```bash
./vendor/bin/testlink validate
```

## Dry-Run in CI

Use dry-run to check what would be synced:

```yaml
jobs:
  check-sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: composer install
      - name: Check sync status
        run: |
          ./vendor/bin/testlink sync --dry-run > sync-preview.txt
          if grep -q "Would modify" sync-preview.txt; then
            echo "Files need syncing!"
            cat sync-preview.txt
            exit 1
          fi
```

## Tips

1. **Run before commits** - Check what needs syncing
2. **Use in PR reviews** - Share dry-run output for review
3. **Combine with verbose** - Get more details
4. **Save output** - Redirect to file for review

```bash
# Save dry-run output
./vendor/bin/testlink sync --dry-run > pending-changes.txt
```
