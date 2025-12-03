# TestLink - Project Context

This document provides essential context for AI assistants working on the TestLink project.

## Project Overview

TestLink is a framework-agnostic test traceability tool for PHP that creates bidirectional links between production code and tests. It supports both Pest and PHPUnit testing frameworks.

## Package Architecture

TestLink uses a **two-package architecture** that is critical to understand:

### `testflowlabs/test-attributes` (Production Dependency)

Contains all PHP attributes used in code:

- `#[TestedBy]` - Used on **production code** to declare which tests verify a method
- `#[LinksAndCovers]` - Used on **test code** to link tests to production methods (with coverage)
- `#[Links]` - Used on **test code** for traceability without coverage

**Why production dependency?** These attributes are placed on production classes. PHP needs the attribute classes available when autoloading production code in any environment (including production).

### `testflowlabs/testlink` (Dev Dependency)

Contains CLI tools and infrastructure:

- `testlink report` - Show coverage links report
- `testlink validate` - Validate bidirectional sync
- `testlink sync` - Auto-sync links between production and test code
- Scanners, parsers, validators, and sync functionality

**Why dev dependency?** These tools only run during development and CI/CD, never in production.

### Installation Pattern

Users must install both packages correctly:

```bash
# Production dependency - attributes for production code
composer require testflowlabs/test-attributes

# Dev dependency - CLI tools
composer require --dev testflowlabs/testlink
```

If testlink is installed as dev-only without test-attributes as production, applications will fail to load production classes that use `#[TestedBy]` in production environments.

## Directory Structure

```
src/
├── Adapter/          # Framework adapters (Pest, PHPUnit)
├── Console/          # CLI application and commands
├── Contract/         # Interfaces
├── Discovery/        # Framework detection
├── Modifier/         # Test file modifiers
├── Parser/           # Test file parsers
├── Registry/         # Link storage
├── Reporter/         # Console and JSON reporters
├── Runtime/          # Pest runtime bootstrap
├── Scanner/          # Attribute scanners
├── Sync/             # Bidirectional sync system
└── Validator/        # Link validators

tests/
├── Fixtures/         # Test fixtures (ProductionCode, TestCode)
└── Unit/             # Unit tests

docs/                 # VitePress documentation site
```

## Key Namespaces

- `TestFlowLabs\TestingAttributes\*` - Attributes (from test-attributes package)
- `TestFlowLabs\TestLink\*` - CLI tools and infrastructure (this package)

## Bidirectional Linking

The core concept is bidirectional linking:

1. **Production → Test**: `#[TestedBy(TestClass::class, 'test_method')]` on production methods
2. **Test → Production**: `linksAndCovers(Class::method)` (Pest) or `#[LinksAndCovers(Class, 'method')]` (PHPUnit)

Both directions should be synchronized. The `testlink validate` command checks for mismatches.

## Testing

```bash
composer test        # Run all checks (rector, pint, phpstan, unit tests, type coverage)
composer test:unit   # Run unit tests only with coverage
composer test:phpstan # Run static analysis
```

## Commit Conventions

- Use Conventional Commits format
- No emojis in commit messages
- No "generated with claude" or "co-authored" messages

## Related Repositories

- https://github.com/TestFlowLabs/test-attributes - PHP attributes package
- https://github.com/TestFlowLabs/testlink - This package (CLI tools)
