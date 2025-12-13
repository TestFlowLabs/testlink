<p align="center">
  <img src="docs/public/testlink-logo.png" alt="TestLink Logo" width="200">
</p>

<h1 align="center">TestLink</h1>

<p align="center">
  <strong>Framework-Agnostic Test Traceability for PHP</strong>
</p>

<p align="center">
  <a href="https://php.net"><img src="https://img.shields.io/badge/php-%5E8.3-blue" alt="PHP Version"></a>
  <a href="https://pestphp.com"><img src="https://img.shields.io/badge/pest-%5E4.0-orange" alt="Pest Support"></a>
  <a href="https://phpunit.de"><img src="https://img.shields.io/badge/phpunit-%5E11.0-purple" alt="PHPUnit Support"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-green" alt="License"></a>
</p>

TestLink creates bidirectional links between your tests and production code. Know exactly which tests cover each method, and which methods each test exercises.

**Supports both Pest and PHPUnit** - use whichever testing framework you prefer, or both in the same project.

## Features

- **Framework Agnostic** - Works with Pest, PHPUnit, or both
- **Bidirectional Linking** - Link tests to methods AND methods to tests
- **Two Link Types** - Coverage links (`linksAndCovers`) and traceability-only links (`links`)
- **Sync Validation** - Detect missing or orphaned links
- **Auto-Sync** - Generate link calls from `#[TestedBy]` attributes
- **Standalone CLI** - Use `testlink` command independently of test runner
- **JSON Export** - CI/CD integration

## Quick Start

### Installation

```bash
# Production dependency - attributes for production code
composer require testflowlabs/test-attributes

# Dev dependency - CLI tools, scanners, validators
composer require --dev testflowlabs/testlink
```

**Why two packages?**

- `test-attributes` must be a **production** dependency because `#[TestedBy]`, `#[LinksAndCovers]`, and `#[Links]` attributes are placed on production classes. PHP needs these attribute classes available when autoloading your production code.
- `testlink` can be a **dev** dependency because it only provides CLI tools (`testlink report`, `testlink validate`, `testlink sync`) that run during development.

### Link from Production Code (Recommended)

```php
// app/Services/UserService.php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'it creates a user')]
    #[TestedBy(UserServiceTest::class, 'it validates email format')]
    public function create(array $data): User
    {
        // ...
    }
}
```

### Link from Tests

#### Pest

```php
// tests/Unit/UserServiceTest.php

// Link + Coverage (triggers coverage tracking)
test('it creates a user')
    ->linksAndCovers(UserService::class.'::create');

// Link only (traceability without coverage)
test('it creates a user integration')
    ->links(UserService::class.'::create');

// Multiple methods
test('it validates and creates')
    ->linksAndCovers(UserService::class.'::validate')
    ->linksAndCovers(UserService::class.'::create');
```

#### PHPUnit

```php
// tests/Unit/UserServiceTest.php
use TestFlowLabs\TestingAttributes\LinksAndCovers;
use TestFlowLabs\TestingAttributes\Links;

class UserServiceTest extends TestCase
{
    // Link + Coverage
    #[LinksAndCovers(UserService::class, 'create')]
    public function testItCreatesUser(): void
    {
        // ...
    }

    // Link only
    #[Links(UserService::class, 'create')]
    public function testItCreatesUserIntegration(): void
    {
        // ...
    }

    // Multiple methods
    #[LinksAndCovers(UserService::class, 'validate')]
    #[LinksAndCovers(UserService::class, 'create')]
    public function testItValidatesAndCreates(): void
    {
        // ...
    }
}
```

## CLI Commands

```bash
# Show coverage links report
testlink report

# Validate bidirectional sync
testlink validate

# Auto-sync from #[TestedBy] to test files
testlink sync

# Preview sync changes (dry run)
testlink sync --dry-run

# Sync and prune orphaned links
testlink sync --prune --force

# Export as JSON
testlink report --json

# Show help
testlink --help
testlink sync --help
```

### Sample Output

```
  Coverage Links Report
  ─────────────────────

  App\Services\UserService

    create()
      → Tests\Unit\UserServiceTest::it creates a user
      → Tests\Unit\UserServiceTest::it validates email format

    update()
      → Tests\Unit\UserServiceTest::it updates a user

  Summary
    Methods with tests: 2
    Total test links: 3
```

## Validation

The `validate` command checks for:

- **Missing Coverage**: Production methods with `#[TestedBy]` but no matching link call
- **Orphaned Links**: Tests claiming links that don't exist
- **Sync Issues**: Mismatched bidirectional links

```bash
$ testlink validate

  Validation Report
  ─────────────────

  Missing Link Calls in Tests
  These #[TestedBy] attributes have no corresponding link calls:

    ✗ App\Services\OrderService::process
      → Tests\Unit\OrderServiceTest::it processes order

  Orphaned Link Calls in Tests
  These link calls have no corresponding #[TestedBy] attributes:

    ! Tests\Unit\PaymentTest::it charges card
      → App\Services\PaymentService::charge

  Validation failed. Run "testlink sync" to fix issues.
```

## Auto-Sync

Automatically generate link calls from `#[TestedBy]` attributes:

```bash
# Preview what will change
testlink sync --dry-run

# Apply sync
testlink sync

# Sync and remove orphaned links
testlink sync --prune --force
```

### How It Works

1. Scans production code for `#[TestedBy]` attributes
2. Locates corresponding test files and test cases
3. Adds missing `linksAndCovers()` calls (Pest) or `#[LinksAndCovers]` attributes (PHPUnit)

### Before Sync (Pest)

```php
// Production code has the attribute
#[TestedBy(UserServiceTest::class, 'it creates a user')]
public function create(): User { }

// Test file is missing link
test('it creates a user', function () { });
```

### After Sync (Pest)

```php
test('it creates a user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

### Before Sync (PHPUnit)

```php
// Production code has the attribute
#[TestedBy(UserServiceTest::class, 'testItCreatesUser')]
public function create(): User { }

// Test file is missing attribute
public function testItCreatesUser(): void { }
```

### After Sync (PHPUnit)

```php
#[LinksAndCovers(UserService::class, 'create')]
public function testItCreatesUser(): void { }
```

## Link Types

| Type | Pest Method | PHPUnit Attribute | Purpose |
|------|-------------|-------------------|---------|
| **Link + Coverage** | `->linksAndCovers()` | `#[LinksAndCovers]` | Traceability + triggers coverage tracking |
| **Link Only** | `->links()` | `#[Links]` | Traceability only, no coverage |

Use **Link + Coverage** for unit tests where you want coverage tracking.
Use **Link Only** for integration/e2e tests where unit coverage is already tracked elsewhere.

## JSON Export

For CI/CD integration:

```bash
testlink report --json > coverage-links.json
```

```json
{
  "links": {
    "App\\Services\\UserService::create": [
      "Tests\\Unit\\UserServiceTest::it creates a user"
    ]
  },
  "summary": {
    "total_methods": 1,
    "total_tests": 1
  }
}
```

## Bootstrap (Pest)

Add to `tests/Pest.php` to enable `linksAndCovers()` and `links()` methods:

```php
use TestFlowLabs\TestLink\Runtime\RuntimeBootstrap;

RuntimeBootstrap::init();
```

## Best Practices

### 1. Prefer `#[TestedBy]` Attributes

Placing links in production code keeps coverage visible where it matters:

```php
#[TestedBy(UserServiceTest::class, 'it creates a user')]
public function create(): User
{
    // Reader immediately knows this method is tested
}
```

### 2. Use Link Types Appropriately

```php
// Unit test - use linksAndCovers for coverage
test('it creates a user with valid data')
    ->linksAndCovers(UserService::class.'::create');

// Integration test - use links for traceability only
test('it creates user through API endpoint')
    ->links(UserService::class.'::create');
```

### 3. Run Validation in CI

```yaml
# .github/workflows/test.yml
- name: Validate coverage links
  run: testlink validate --strict
```

## Hybrid Projects

TestLink seamlessly supports projects using both Pest and PHPUnit:

```bash
$ testlink report

  Coverage Links Report
  ─────────────────────

  Detected frameworks: pest, phpunit

  App\Services\UserService
    create()
      → Tests\Unit\UserServiceTest::it creates a user (pest)
      → Tests\Integration\UserApiTest::testCreateUser (phpunit)
```

## Documentation

Full documentation is available at the [TestLink Documentation](https://testflowlabs.github.io/testlink/).

- **[Tutorials](https://testflowlabs.github.io/testlink/tutorials/)** - Learn TestLink step-by-step with TDD and BDD workflows
- **[How-to Guides](https://testflowlabs.github.io/testlink/how-to/)** - Solve specific problems and tasks
- **[Reference](https://testflowlabs.github.io/testlink/reference/)** - CLI commands, attributes, and configuration
- **[Explanation](https://testflowlabs.github.io/testlink/explanation/)** - Understand bidirectional linking concepts

## Ecosystem

TestLink is part of the TestFlowLabs ecosystem:

| Package | Description |
|---------|-------------|
| [test-attributes](https://github.com/TestFlowLabs/test-attributes) | PHP attributes for test metadata (`#[LinksAndCovers]`, `#[Links]`) |
| [testlink](https://github.com/TestFlowLabs/testlink) | This package - Test traceability, `#[TestedBy]` attribute, CLI tools |

## Contributing

1. Fork the repository
2. Create a feature branch
3. Run `composer test` to ensure all checks pass
4. Submit a pull request

## License

MIT License. See [LICENSE](LICENSE) for details.
