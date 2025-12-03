# Installation

## Requirements

- PHP 8.3 or higher
- Pest 4.0+ and/or PHPUnit 11.0+

TestLink supports both frameworks individually or together in hybrid projects.

## Install via Composer

### Using `#[TestedBy]` on Production Code (Recommended)

If you plan to use `#[TestedBy]` attributes on your production code, install as a **production** dependency:

```bash
composer require testflowlabs/testlink
```

This is necessary because PHP needs the `TestedBy` attribute class available when loading your production classes.

### CLI Tools Only

If you only use the CLI tools (`testlink report`, `testlink validate`, etc.) without `#[TestedBy]` attributes on production code:

```bash
composer require --dev testflowlabs/testlink
```

::: tip Dependency Note
Both installations automatically include the `testflowlabs/test-attributes` package, which provides `#[LinksAndCovers]` and `#[Links]` attributes for your test code.
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

Detected frameworks: pest, phpunit
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

## Next Steps

- [Quick Start](/introduction/quick-start) - Create your first coverage links
- [Linking from Tests](/guide/covers-method-helper) - Link tests to production code
- [CLI Commands](/guide/cli-commands) - Learn all available commands
