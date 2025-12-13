# Configuration

TestLink configuration options and settings.

## Overview

TestLink is designed to work with zero configuration. It automatically:
- Detects Pest and PHPUnit
- Uses Composer's autoload configuration
- Scans standard directories

## Command-Line Options

All configuration is done via command-line options.

### Path Filtering

Filter operations to specific directories:

```bash
# Report for specific directory
./vendor/bin/testlink report --path=src/Services

# Validate specific path
./vendor/bin/testlink validate --path=src/Services

# Sync specific path
./vendor/bin/testlink sync --path=src/Services
```

### Output Format

Control output format:

```bash
# JSON output
./vendor/bin/testlink report --json
./vendor/bin/testlink validate --json

# Verbose output
./vendor/bin/testlink report --verbose
./vendor/bin/testlink validate --verbose
```

### Validation Strictness

```bash
# Normal - errors only
./vendor/bin/testlink validate

# Strict - errors and warnings
./vendor/bin/testlink validate --strict
```

### Sync Options

```bash
# Preview changes
./vendor/bin/testlink sync --dry-run

# Remove orphans
./vendor/bin/testlink sync --prune

# Only add links
./vendor/bin/testlink sync --link-only

# Force overwrite
./vendor/bin/testlink sync --force
```

### Pair Options

```bash
# Preview changes
./vendor/bin/testlink pair --dry-run

# Specific placeholder
./vendor/bin/testlink pair --placeholder=@name
```

## Framework Detection

TestLink automatically detects testing frameworks from `composer.json`:

### Pest detection

```json
{
    "require-dev": {
        "pestphp/pest": "^2.0"
    }
}
```

### PHPUnit detection

```json
{
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

### Both frameworks

When both are installed, TestLink supports both:

```json
{
    "require-dev": {
        "pestphp/pest": "^2.0",
        "phpunit/phpunit": "^10.0"
    }
}
```

## Directory Structure

TestLink uses Composer's PSR-4 autoload for class discovery:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

### Standard structure

```
project/
├── src/                    # Production code (scanned for #[TestedBy])
│   └── Services/
│       └── UserService.php
├── tests/                  # Test code (scanned for links)
│   └── Unit/
│       └── UserServiceTest.php
└── composer.json
```

## Placeholder Syntax

Placeholder format is fixed:

- Must start with `@`
- Followed by a letter
- Can contain: letters, numbers, hyphens, underscores

Examples:
- `@A` ✓
- `@user-create` ✓
- `@UserCreate` ✓
- `@user_create` ✓
- `@123` ✗
- `@-test` ✗

## Exit Codes

| Command | Code 0 | Code 1 |
|---------|--------|--------|
| `report` | Always | Error scanning |
| `validate` | All valid | Errors found |
| `sync` | Success | Error syncing |
| `pair` | All resolved | Orphan placeholders |

## CI Configuration

### GitHub Actions example

```yaml
name: TestLink

on: [push, pull_request]

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: ./vendor/bin/testlink validate
```

### GitLab CI example

```yaml
testlink:
  stage: test
  script:
    - composer install
    - ./vendor/bin/testlink validate
```

## Composer Scripts

Add TestLink to your composer scripts:

```json
{
    "scripts": {
        "test": [
            "@test:unit",
            "@test:links"
        ],
        "test:unit": "pest",
        "test:links": "testlink validate",
        "links:report": "testlink report",
        "links:sync": "testlink sync"
    }
}
```

Usage:

```bash
composer test:links
composer links:report
composer links:sync
```

## Environment Variables

TestLink does not currently use environment variables. All configuration is via command-line options.

## Future Configuration

Future versions may support:
- Configuration files
- Custom scan paths
- Link format preferences
- Integration settings

Check the [GitHub repository](https://github.com/testflowlabs/testlink) for updates.
