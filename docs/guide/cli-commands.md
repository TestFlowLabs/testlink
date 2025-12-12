# CLI Commands

TestLink provides a standalone CLI (`testlink`) that works with any testing framework.

## Choosing the Right Command

<div class="diagram-container">
  <img src="/diagrams/cli-commands-decision.svg" alt="CLI Commands Decision Tree" />
</div>

## Standalone CLI

The standalone CLI works with any testing framework (Pest, PHPUnit, or both).

### Installation

After installing via Composer, the `testlink` command is available at:

```bash
./vendor/bin/testlink
```

### Getting Started

When you first run testlink, it shows available commands and detected framework:

```bash
testlink
```

Output (Pest project):

```
  TestLink dev-master

  Detected frameworks: pest (phpunit compatible)


  USAGE
    testlink <command> [options]

  COMMANDS
    • report      Show coverage links report
    • validate    Validate coverage link synchronization
    • sync        Sync coverage links across test files
    • pair        Resolve placeholder markers into real links

  GLOBAL OPTIONS
    • --help, -h        Show help information
    • --version, -v     Show version
    • --verbose         Show detailed output
    • --no-color        Disable colored output

  Run "testlink <command> --help" for command-specific help.
```

Output (PHPUnit-only project):

```
  TestLink dev-master

  Detected frameworks: phpunit

  ...
```

### Commands

#### Report

Show coverage links from `#[TestedBy]` attributes:

```bash
testlink report
```

Output (with links):

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

Output (empty project - no links yet):

```
  Coverage Links Report
  ─────────────────────
  No coverage links found.

  Add coverage links to your test files:

    Pest:    ->linksAndCovers(UserService::class.'::create')
    PHPUnit: #[LinksAndCovers(UserService::class, 'create')]
```

Output (with @see tags):

```
  Coverage Links Report
  ─────────────────────

  App\Services\UserService

    create()
      → Tests\Unit\UserServiceTest::it creates a user

  @see Tags
  ─────────

  Production code → Tests:
    App\Services\UserService::create
      → Tests\Unit\UserServiceTest::test_creates_user

  Test code → Production:
    Tests\Unit\UserServiceTest::test_creates_user
      → App\Services\UserService::create

  Summary
    Methods with tests: 1
    Total test links: 1
    @see tags: 2
```

::: tip @see Tags
@see tags provide full IDE method navigation. See [@see Tags Guide](/guide/see-tags) for details.
:::

Options:

| Option | Description |
|--------|-------------|
| `--json` | Output as JSON |
| `--path=<dir>` | Limit scan to directory |
| `--framework=<fw>` | Filter by framework (pest, phpunit) |

#### Validate

Verify all coverage links are synchronized and detect unresolved placeholders:

```bash
testlink validate
```

Output (empty project - no links yet):

```
  Validation Report
  ─────────────────

  Link Summary
    PHPUnit attribute links: 0
    Pest method chain links: 0
    Total links: 0

  No coverage links found.

  Add coverage links to your test files:

    Pest:    ->linksAndCovers(UserService::class.'::create')
    PHPUnit: #[LinksAndCovers(UserService::class, 'create')]
```

Success output (with links):

```
  Validation Report
  ─────────────────

  Link Summary
  ────────────

    PHPUnit attribute links: 5
    Pest method chain links: 10
    Total links: 15

  ✓ All links are valid!
```

Output with unresolved placeholders:

```
  Validation Report
  ─────────────────

  Unresolved Placeholders
  ───────────────────────

    ⚠ @user-create  (1 production, 2 tests)
    ⚠ @A  (2 production, 0 tests)

    ⚠ Run "testlink pair" to resolve placeholders.

  Link Summary
  ────────────

    PHPUnit attribute links: 5
    Pest method chain links: 10
    Total links: 15

  ✓ All links are valid!
```

Failure output (duplicate links):

```
  Validation Report
  ─────────────────

  Duplicate Links Found
  ─────────────────────

    ! Tests\Unit\UserServiceTest::test_creates_user
      → App\Services\UserService::create

  ⚠ Consider using only one linking method per test.
```

Output (with orphan @see tags):

```
  Validation Report
  ─────────────────

  Orphan @see Tags
  ────────────────

    ⚠ @see \Tests\Unit\OldTest::deleted_test
      in src/Services/UserService.php:45

  Link Summary
  ────────────

    PHPUnit attribute links: 5
    Pest method chain links: 10
    @see tags: 4 (1 orphan)
    Total links: 15

  ✓ All links are valid!
```

::: warning Orphan @see Tags
Orphan @see tags point to tests or methods that no longer exist. Use `testlink sync --prune --force` to remove them.
:::

Options:

| Option | Description |
|--------|-------------|
| `--strict` | Fail on warnings (including unresolved placeholders) |
| `--json` | Output as JSON |
| `--path=<dir>` | Limit scan to directory |

::: tip Placeholder Detection
The validate command automatically detects unresolved placeholders. In normal mode, this shows a warning but doesn't fail. Use `--strict` to fail when placeholders are found.
:::

Example with `--strict` (fails when placeholders found):

```bash
testlink validate --strict
```

```
  Validation Report
  ─────────────────

  Unresolved Placeholders
  ───────────────────────

    ⚠ @user-create  (1 production, 2 tests)

    ✗ Validation failed: unresolved placeholders found.
    Run "testlink pair" to resolve placeholders.

Exit code: 1
```

#### Sync

Synchronize `#[TestedBy]` attributes to test files:

```bash
testlink sync
```

Output (empty project - nothing to sync):

```
  Syncing Coverage Links
  ──────────────────────
  Running in dry-run mode. No files will be modified.

  Scanning test files for coverage links...

  No links found to sync.
  No changes needed. All links are up to date.
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

#### Pair

Resolve placeholder markers (`@A`, `@user-create`) into real test-production links:

```bash
testlink pair
```

Placeholders are temporary markers used during rapid TDD/BDD development. Instead of writing full class references, you use short markers that get resolved later.

Output (empty project - no placeholders):

```
  Pairing Placeholders
  ────────────────────
  Running in dry-run mode. No files will be modified.

  Scanning for placeholders...

  No placeholders found.

  Placeholders use @syntax, for example:
    Production: #[TestedBy('@A')]
    Test (Pest): ->linksAndCovers('@A')
    Test (PHPUnit): #[LinksAndCovers('@A')]
```

Output (with placeholders):

```
  Pairing Placeholders
  ────────────────────

  Scanning for placeholders...

  Found Placeholders
  ──────────────────

    ✓ @user-create  1 production × 2 tests = 2 links
    ✓ @A  2 production × 3 tests = 6 links

  Production Files
  ────────────────

    src/Services/UserService.php
      @user-create → UserServiceTest::it creates a user

  Test Files
  ──────────

    tests/Unit/UserServiceTest.php
      @user-create → UserService::create

  ✓ Pairing complete. Modified 2 file(s) with 8 change(s).
```

Output (nested describe blocks - Pest):

```
  Found Placeholders
    ✓ @nested  2 production × 3 tests = 6 links

  Production Files
    src/Testing/NestedService.php
      @nested → NestedServiceTest::NestedService > create method > creates with valid data
      @nested → NestedServiceTest::NestedService > delete method > soft deletes
```

::: tip Nested Describe Identifiers
When using nested `describe` blocks in Pest, test identifiers include the full path:
`OuterDescribe > InnerDescribe > test name`
:::

Options:

| Option | Description |
|--------|-------------|
| `--dry-run` | Preview changes without applying |
| `--placeholder=@X` | Resolve only the specified placeholder |

Examples:

```bash
# Preview all placeholder resolutions
testlink pair --dry-run

# Apply all placeholder resolutions
testlink pair

# Resolve only a specific placeholder
testlink pair --placeholder=@user-create
```

::: tip Placeholder Syntax
Placeholders must start with `@` followed by a letter. Valid examples: `@A`, `@B`, `@user-create`, `@MyFeature123`.

See the [Placeholder Pairing Guide](/guide/placeholder-pairing) for detailed usage.
:::

### Global Options

Available for all commands:

| Option | Description |
|--------|-------------|
| `--help, -h` | Show help |
| `--version, -v` | Show version |
| `--verbose` | Show detailed output |
| `--no-color` | Disable colored output |

## Error Handling

### Unknown Command

```bash
testlink unknown
```

Output:

```
  Unknown command: unknown

  COMMANDS
    • report      Show coverage links report
    • validate    Validate coverage link synchronization
    • sync        Sync coverage links across test files
    • pair        Resolve placeholder markers into real links
```

Exit code: `1`

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
