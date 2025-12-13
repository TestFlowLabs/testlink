# Two-Package Architecture

Why TestLink is split into two packages and how this ensures your production code works everywhere.

## The Architecture

TestLink is distributed as two separate Composer packages:

```
┌─────────────────────────────────────┐
│  testflowlabs/test-attributes       │  ← Production dependency
│  - #[TestedBy]                      │
│  - #[LinksAndCovers]                │
│  - #[Links]                         │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│  testflowlabs/testlink              │  ← Dev dependency
│  - testlink report                  │
│  - testlink validate                │
│  - testlink sync                    │
│  - testlink pair                    │
└─────────────────────────────────────┘
```

## Why Two Packages?

### The Problem with One Package

Imagine TestLink was a single package installed as a dev dependency:

```bash
composer require --dev testflowlabs/testlink
```

Now you add `#[TestedBy]` to your production code:

```php
// src/UserService.php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'test_creates_user')]
    public function create(array $data): User
    {
        // ...
    }
}
```

**What happens in production?**

```bash
composer install --no-dev  # Production deployment
```

The `TestFlowLabs\TestingAttributes\TestedBy` class doesn't exist!

```
PHP Fatal error: Class "TestFlowLabs\TestingAttributes\TestedBy" not found
```

Your production application crashes because PHP tries to load the attribute class when autoloading `UserService`.

### The Solution: Separate Packages

By splitting into two packages:

1. **`test-attributes`** contains only the attribute classes
2. **`testlink`** contains the CLI tools

You install them differently:

```bash
# Production dependency - always available
composer require testflowlabs/test-attributes

# Dev dependency - only during development
composer require --dev testflowlabs/testlink
```

Now in production:

```bash
composer install --no-dev
```

- ✓ `TestedBy` class exists (from `test-attributes`)
- ✓ `UserService` loads correctly
- ✓ Application works
- ✗ CLI tools not installed (not needed in production)

## What's in Each Package?

### test-attributes (Production)

Minimal package containing only PHP 8 attributes:

```php
namespace TestFlowLabs\TestingAttributes;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class TestedBy
{
    public function __construct(
        public string $testClass,
        public ?string $testMethod = null
    ) {}
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class LinksAndCovers
{
    public function __construct(
        public string $class,
        public ?string $method = null
    ) {}
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Links
{
    public function __construct(
        public string $class,
        public ?string $method = null
    ) {}
}
```

This package:
- Has zero dependencies
- Contains only attribute definitions
- Is safe for production
- Has minimal footprint

### testlink (Dev)

Full-featured CLI package:

```
src/
├── Console/          # CLI application
│   └── Command/      # report, validate, sync, pair
├── Scanner/          # Attribute scanners
├── Parser/           # Test file parsers
├── Validator/        # Link validators
├── Sync/             # Bidirectional sync
├── Placeholder/      # Placeholder pairing
└── Reporter/         # Output formatters
```

This package:
- Depends on `test-attributes`
- Contains CLI tools
- Only needed during development
- Has additional dependencies (Symfony Console, etc.)

## Installation Pattern

### Correct Installation

```bash
# Step 1: Install attributes for production code
composer require testflowlabs/test-attributes

# Step 2: Install CLI tools for development
composer require --dev testflowlabs/testlink
```

Your `composer.json`:

```json
{
    "require": {
        "testflowlabs/test-attributes": "^1.0"
    },
    "require-dev": {
        "testflowlabs/testlink": "^1.0"
    }
}
```

### Common Mistake

```bash
# WRONG: Only installing as dev dependency
composer require --dev testflowlabs/testlink
```

This installs `testlink` which depends on `test-attributes`, but both end up in `require-dev`. In production:

```bash
composer install --no-dev  # Neither package installed!
```

Result: Production crashes when loading classes with `#[TestedBy]`.

## Dependency Graph

```
Your Production Code
        │
        ├── uses #[TestedBy]
        │         │
        │         └── requires testflowlabs/test-attributes (PRODUCTION)
        │
Your Test Code
        │
        ├── uses ->linksAndCovers()
        │         │
        │         └── loaded by testflowlabs/testlink (DEV)
        │
        └── uses #[LinksAndCovers]
                  │
                  └── requires testflowlabs/test-attributes (PRODUCTION)
```

## Why Attributes in Production?

You might ask: "Why are `#[LinksAndCovers]` and `#[Links]` in the production package? They're only used in tests!"

### PHP Attribute Loading

PHP loads attribute classes when the class containing them is loaded:

```php
// tests/UserServiceTest.php
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_user(): void
    {
        // When PHP loads this class, it needs LinksAndCovers to exist
    }
}
```

If attributes were in `testlink` (dev-only), and you ran tests in a clean CI environment:

```bash
composer install  # Installs all dependencies including dev
./vendor/bin/pest  # Works fine
```

But with certain CI caching or partial installs, you might get:

```
Class "TestFlowLabs\TestingAttributes\LinksAndCovers" not found
```

### Consistency is Safer

By putting all attributes in `test-attributes`:

- All attribute classes are always available together
- No confusion about which package provides which attribute
- Tests work reliably in any environment

## Practical Implications

### For Your composer.json

```json
{
    "require": {
        "php": "^8.2",
        "testflowlabs/test-attributes": "^1.0"
    },
    "require-dev": {
        "pestphp/pest": "^2.0",
        "testflowlabs/testlink": "^1.0"
    }
}
```

### For CI/CD

```yaml
# .github/workflows/ci.yml
jobs:
  test:
    steps:
      - run: composer install  # All dependencies
      - run: ./vendor/bin/pest
      - run: ./vendor/bin/testlink validate

  deploy:
    steps:
      - run: composer install --no-dev  # Only production
      # testlink CLI not available, but that's fine
      # test-attributes IS available for production code
```

### For Docker

```dockerfile
# Development
FROM php:8.2
RUN composer install

# Production
FROM php:8.2
RUN composer install --no-dev --optimize-autoloader
# App works because test-attributes is a production dependency
```

## Summary

The two-package architecture ensures:

1. **Production stability** - Attributes are always available
2. **Minimal footprint** - Only attributes in production, not CLI tools
3. **Clear separation** - Production vs. development concerns
4. **Reliable CI** - Tests work regardless of install method

Remember: **Always install `test-attributes` as a production dependency.**

## See Also

- [Getting Started](/tutorials/getting-started) - Correct installation steps
- [#[TestedBy]](/reference/attributes/testedby) - Production code attribute
- [#[LinksAndCovers]](/reference/attributes/linksandcovers) - Test code attribute
