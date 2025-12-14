# report

Display coverage links from `#[TestedBy]` attributes and test files.

## Synopsis

```bash
testlink report [options]
```

## Description

The `report` command scans your codebase and displays all coverage links between production code and tests. It reads:

- `#[TestedBy]` attributes from production classes
- `linksAndCovers()` and `links()` from Pest tests
- `#[LinksAndCovers]` and `#[Links]` from PHPUnit tests
- `@see` tags from docblocks

## Options

| Option | Description |
|--------|-------------|
| `--json` | Output in JSON format |
| `--verbose`, `-v` | Show detailed information |
| `--path=<path>` | Filter by directory or file path |

## Examples

### Basic report

```bash
./vendor/bin/testlink report
```

Output:
```
  Coverage Links Report
  ─────────────────────

  App\Services\UserService
    create()
    → Tests\Unit\UserServiceTest::test_creates_user
    → Tests\Unit\UserServiceTest::test_validates_email

    update()
    → Tests\Unit\UserServiceTest::test_updates_user

  Summary
  ───────
    Methods with tests:       2
    Total test links:         3
    @see tags:                0

  ✓ Report complete.
```

### JSON output

```bash
./vendor/bin/testlink report --json
```

Output:
```json
{
  "classes": {
    "App\\Services\\UserService": {
      "methods": {
        "create": {
          "tests": [
            "Tests\\Unit\\UserServiceTest::test_creates_user",
            "Tests\\Unit\\UserServiceTest::test_validates_email"
          ]
        },
        "update": {
          "tests": [
            "Tests\\Unit\\UserServiceTest::test_updates_user"
          ]
        },
        "delete": {
          "tests": []
        }
      }
    }
  },
  "summary": {
    "methodsWithTests": 2,
    "methodsWithoutTests": 1,
    "totalLinks": 3
  }
}
```

### Filter by path

```bash
./vendor/bin/testlink report --path=src/Services
```

Only shows classes in `src/Services/`.

### Verbose output

```bash
./vendor/bin/testlink report --verbose
```

Shows additional details:
- File paths
- Line numbers
- Link sources (attribute, method chain, @see tag)

## Output Sections

### Class Section

Each class with `#[TestedBy]` attributes is listed:

```
  App\Services\UserService
    create()
    → Tests\UserServiceTest::test_creates_user
```

### Methods Without Tests

Methods with no linked tests show:

```
    delete()
    (no tests linked)
```

### Summary

Aggregated statistics at the end of every report:

```
  Summary
  ───────
    Methods with tests:       2
    Total test links:         3
    @see tags:                0

  ✓ Report complete.
```

## Exit Codes

| Code | Meaning |
|------|---------|
| `0` | Success |
| `1` | Error (unable to scan) |

The report command always exits `0` even if there are methods without tests. Use `validate` for stricter checking.

## Use Cases

### View all coverage

```bash
./vendor/bin/testlink report
```

### Generate report for CI

```bash
./vendor/bin/testlink report --json > coverage-links.json
```

### Check specific module

```bash
./vendor/bin/testlink report --path=src/Billing
```

