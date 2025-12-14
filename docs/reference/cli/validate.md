# validate

Validate coverage links between production code and tests.

## Synopsis

```bash
testlink validate [options]
```

## Description

The `validate` command checks that:

1. All `#[TestedBy]` attributes point to existing tests
2. All test links have matching `#[TestedBy]` (bidirectional)
3. No duplicate links exist
4. No unresolved placeholders remain
5. @see tags use fully qualified class names

## Options

| Option | Description |
|--------|-------------|
| `--strict` | Fail on warnings (not just errors) |
| `--json` | Output in JSON format |
| `--fix` | Auto-fix non-FQCN @see tags |
| `--verbose`, `-v` | Show detailed information |
| `--path=<path>` | Filter by directory or file path |

## Examples

### Basic validation

```bash
./vendor/bin/testlink validate
```

Output (success):
```
  Validation Report
  ─────────────────

  Summary
  ───────
    PHPUnit attribute links:  10
    Pest method chain links:  5
    @see tags:                0
    Total links:              15

  ✓ Validation complete. All links are valid!
```

Output (failure):
```
  Validation Report
  ─────────────────

  Orphan @see Tags
    ✗ Tests\UserServiceTest::test_old_method
      → src/UserService.php:25

  Unresolved Placeholders
    ⚠ @user-create  (1 production, 1 tests)

  Summary
  ───────
    PHPUnit attribute links:  10
    Pest method chain links:  5
    @see tags:                0
    Total links:              15

    Issues found:             2
      Orphan @see tags:       1
      Unresolved placeholders: 1 (warning)

  ✓ Validation complete with issues.
```

### Strict mode

```bash
./vendor/bin/testlink validate --strict
```

Fails on warnings as well as errors:
- Missing bidirectional links (warning → error)
- FQCN issues in @see tags (warning → error)

### JSON output

```bash
./vendor/bin/testlink validate --json
```

Output:
```json
{
  "valid": false,
  "summary": {
    "phpunitLinks": 10,
    "pestLinks": 5,
    "totalLinks": 15
  },
  "errors": {
    "orphanTestedBy": [
      {
        "class": "App\\UserService",
        "method": "create",
        "target": "Tests\\UserServiceTest::test_old_method",
        "reason": "test not found"
      }
    ],
    "unresolvedPlaceholders": [
      {
        "placeholder": "@user-create",
        "production": ["App\\UserService::create"],
        "tests": ["Tests\\UserServiceTest::test_creates_user"]
      }
    ]
  },
  "warnings": []
}
```

## Validation Checks

### Orphan TestedBy

`#[TestedBy]` points to non-existent test:

```
  ✗ Found orphan TestedBy:
    App\UserService::create
      → Tests\UserServiceTest::test_old_method (test not found)
```

**Fix:** Update or remove the `#[TestedBy]` attribute.

### Missing TestedBy

Test has link but production has no `#[TestedBy]`:

```
  ⚠ Found missing TestedBy (warning):
    Tests\UserServiceTest::test_creates_user
      → App\UserService::create (no TestedBy)
```

**Fix:** Add `#[TestedBy]` to production or run `sync`.

### Duplicate Links

Same link appears multiple times:

```
  ✗ Found duplicate:
    Tests\UserServiceTest::test_creates_user → App\UserService::create
      Sources: PHPUnit attribute, Pest method chain
```

**Fix:** Remove one of the duplicate declarations.

### Unresolved Placeholder

Placeholder hasn't been resolved:

```
  ✗ Found unresolved placeholder:
    @user-create
      Production: App\UserService::create
      Tests: Tests\UserServiceTest::test_creates_user
```

**Fix:** Run `testlink pair` to resolve.

### FQCN Issue

@see tag doesn't use fully qualified name:

```
  Non-FQCN @see Tags
  These @see tags should use fully qualified class names:

    src/TestLink/UserService.php
      ✗ Line 15: Tests\TestLink\UserServiceTest::creates
        → Could not resolve 'Tests\TestLink\UserServiceTest' - not found in use statements
```

**Fix:** Run `testlink validate --fix` to auto-convert.

### Auto-Fix Non-FQCN @see Tags

Automatically convert short class names to FQCN:

```bash
# Preview what would be fixed
./vendor/bin/testlink validate --fix --dry-run

# Apply fixes
./vendor/bin/testlink validate --fix
```

Output:
```
  Validation Report
  ─────────────────

  FQCN Conversion Results
    ✓ src/TestLink/UserService.php
      + Tests\TestLink\UserServiceTest::creates
        → \Tests\TestLink\UserServiceTest::creates

  ✓ Converted 1 @see tag(s) in 1 file(s).

  Summary
  ───────
    PHPUnit attribute links:  5
    Pest method chain links:  3
    @see tags:                4
    Total links:              12

    Issues fixed:             1

  ✓ Validation complete. All links are valid!
```

The resolver uses PHP `use` statements to determine the correct FQCN.

## Exit Codes

| Code | Meaning |
|------|---------|
| `0` | All valid (no errors) |
| `1` | Validation errors found |

In strict mode, warnings also cause exit code `1`.

## CI Integration

### Basic CI check

```yaml
- name: Validate TestLink
  run: ./vendor/bin/testlink validate
```

### Strict CI check

```yaml
- name: Validate TestLink (strict)
  run: ./vendor/bin/testlink validate --strict
```

### Save report

```yaml
- name: Validate TestLink
  run: ./vendor/bin/testlink validate --json > validation.json
  continue-on-error: true

- name: Upload report
  uses: actions/upload-artifact@v4
  with:
    name: testlink-validation
    path: validation.json
```

