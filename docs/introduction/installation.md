# Installation

## Requirements

- PHP 8.3 or higher
- Pest 4.0+ and/or PHPUnit 11.0+

TestLink supports both frameworks individually or together in hybrid projects.

## Install via Composer

TestLink uses a two-package architecture:

```bash
# Production dependency - attributes for production code
composer require testflowlabs/test-attributes

# Dev dependency - CLI tools, scanners, validators
composer require --dev testflowlabs/testlink
```

### Why Two Packages?

The `test-attributes` package must be a **production** dependency because:

- `#[TestedBy]` attributes are placed on production code (services, controllers, models)
- `#[LinksAndCovers]` and `#[Links]` attributes may reference production classes
- PHP needs these attribute classes available when autoloading your code

The `testlink` package can be a **dev** dependency because:

- It only provides CLI tools (`testlink report`, `testlink validate`, `testlink sync`)
- These tools run during development and CI/CD, not in production
- It includes scanners, validators, and sync functionality

::: warning Important
If you install `testlink` as a dev dependency but don't install `test-attributes` as a production dependency, your application will fail to load production classes that use `#[TestedBy]` attributes in production environments.
:::

## Verify Installation

After installation, verify the CLI is working:

```bash
testlink --help
```

You should see:

```
TestLink - Test Coverage Traceability

USAGE
  testlink <command> [options]

COMMANDS
  report      Show coverage links report
  validate    Validate coverage link synchronization
  sync        Sync coverage links across test files

Detected frameworks: pest (phpunit compatible)
```

## Framework Setup

### Pest

To enable `linksAndCovers()` and `links()` method chaining, add to your `tests/Pest.php`:

```php
// tests/Pest.php
use TestFlowLabs\TestLink\Runtime\RuntimeBootstrap;

RuntimeBootstrap::init();
```

Now you can use:

```php
test('creates a user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

### PHPUnit

No additional setup required. Just use the attributes:

```php
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_user(): void
    {
        // ...
    }
}
```

### Hybrid Projects (Pest + PHPUnit)

If you use both frameworks, set up the Pest bootstrap. PHPUnit will work automatically:

```php
// tests/Pest.php
use TestFlowLabs\TestLink\Runtime\RuntimeBootstrap;

RuntimeBootstrap::init();
```

## IDE Support

### PhpStorm

PhpStorm automatically recognizes PHP 8 attributes. If needed:
1. Invalidate caches and restart (File → Invalidate Caches)
2. Ensure the vendor directory is indexed

### VS Code

Install PHP Intelephense or PHP Tools extension for attribute support.

