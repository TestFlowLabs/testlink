# CLI Commands

TestLink provides two ways to run commands: the standalone CLI (`testlink`) and Pest plugin integration (`pest --*`).

## Standalone CLI (Recommended)

The standalone CLI works with any testing framework (Pest, PHPUnit, or both).

### Installation

After installing via Composer, the `testlink` command is available at:

```bash
./vendor/bin/testlink
```

### Commands

#### Report

Show coverage links from `#[TestedBy]` attributes:

```bash
testlink report
```

Output:

```
  Coverage Links Report
  ─────────────────────

  App\Services\UserService

    create()
      → Tests\Unit\UserServiceTest::it creates a user
      → Tests\Unit\UserServiceTest::it validates email

    update()
      → Tests\Unit\UserServiceTest::it updates a user

  Summary
    Methods with tests: 2
    Total test links: 3
```

Options:

| Option | Description |
|--------|-------------|
| `--json` | Output as JSON |
| `--path=<dir>` | Limit scan to directory |
| `--framework=<fw>` | Filter by framework (pest, phpunit) |

#### Validate

Verify all coverage links are synchronized:

```bash
testlink validate
```

Success output:

```
  Validation Report
  ─────────────────

  ✓ All links are synchronized!
```

Failure output:

```
  Validation Report
  ─────────────────

  Missing Link Calls in Tests
  These #[TestedBy] attributes have no corresponding link calls:

    ✗ App\Services\UserService::create
      → Tests\Unit\UserServiceTest::it creates user

  Validation failed. Run "testlink sync" to fix issues.
```

Options:

| Option | Description |
|--------|-------------|
| `--strict` | Fail on warnings |
| `--json` | Output as JSON |
| `--path=<dir>` | Limit scan to directory |

#### Sync

Synchronize `#[TestedBy]` attributes to test files:

```bash
testlink sync
```

This reads all `#[TestedBy]` attributes and adds corresponding links to test files:
- **Pest**: Adds `->linksAndCovers()` method calls
- **PHPUnit**: Adds `#[LinksAndCovers]` attributes

Options:

| Option | Description |
|--------|-------------|
| `--dry-run` | Preview changes without applying |
| `--link-only` | Use `links()` / `#[Links]` instead of `linksAndCovers()` / `#[LinksAndCovers]` |
| `--prune` | Remove orphaned link calls |
| `--force` | Required with --prune for safety |
| `--path=<dir>` | Limit sync to directory |
| `--framework=<fw>` | Target framework (pest, phpunit, auto) |

Examples:

```bash
# Preview changes
testlink sync --dry-run

# Apply changes
testlink sync

# Sync with links() only (no coverage)
testlink sync --link-only

# Sync and prune orphans
testlink sync --prune --force

# Sync specific directory
testlink sync --path=src/Services

# Target specific framework
testlink sync --framework=phpunit
```

### Global Options

Available for all commands:

| Option | Description |
|--------|-------------|
| `--help, -h` | Show help |
| `--version, -v` | Show version |
| `--verbose` | Show detailed output |
| `--no-color` | Disable colored output |

## Pest Plugin (Alternative)

If you're using Pest, you can also use these commands through the Pest CLI:

### Report

```bash
pest --coverage-links
```

### Report as JSON

```bash
pest --coverage-links-json
```

### Validate

```bash
pest --validate-coverage-links
```

### Sync

```bash
pest --sync-coverage-links
pest --sync-coverage-links --dry-run
pest --sync-coverage-links --link-only
pest --sync-coverage-links --prune --force
```

### Help

```bash
pest --help-testlink
```

::: tip Choosing Between CLIs
The standalone `testlink` CLI is recommended because:
- Works with both Pest and PHPUnit
- Framework-agnostic output
- Better for CI/CD pipelines
- Consistent behavior across projects
:::

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Validation failed or errors occurred |

## CI Usage

### GitHub Actions

```yaml
name: Test

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install
      - name: Validate Coverage Links
        run: ./vendor/bin/testlink validate --strict
      - name: Run Tests
        run: ./vendor/bin/pest  # or ./vendor/bin/phpunit
```

### GitLab CI

```yaml
test:
  stage: test
  script:
    - composer install
    - ./vendor/bin/testlink validate --strict
    - ./vendor/bin/pest  # or ./vendor/bin/phpunit
```

### CircleCI

```yaml
jobs:
  test:
    docker:
      - image: cimg/php:8.3
    steps:
      - checkout
      - run: composer install
      - run: ./vendor/bin/testlink validate --strict
      - run: ./vendor/bin/pest  # or ./vendor/bin/phpunit
```

## JSON Output

Both CLI tools support JSON output for CI/CD integration:

```bash
# Standalone CLI
testlink report --json > coverage-links.json

# Pest plugin
pest --coverage-links-json > coverage-links.json
```

Example JSON output:

```json
{
  "links": {
    "App\\Services\\UserService::create": [
      "Tests\\Unit\\UserServiceTest::it creates a user",
      "Tests\\Unit\\UserServiceTest::it validates email"
    ]
  },
  "summary": {
    "total_methods": 1,
    "total_tests": 2
  }
}
```
