# CLI Overview

TestLink provides a command-line interface for managing test-to-code links.

## Usage

```bash
./vendor/bin/testlink <command> [options]
```

## Available Commands

| Command | Description |
|---------|-------------|
| `report` | Display coverage links |
| `validate` | Check link integrity |
| `sync` | Synchronize links |
| `pair` | Resolve placeholders |

## Global Options

These options work with all commands:

| Option | Description |
|--------|-------------|
| `--help`, `-h` | Show help for command |
| `--version`, `-V` | Show version |

## Command Details

### report

Show all coverage links from `#[TestedBy]` attributes:

```bash
./vendor/bin/testlink report
./vendor/bin/testlink report --json
./vendor/bin/testlink report --path=src/Services
```

[Full reference →](./report)

### validate

Validate link integrity and synchronization:

```bash
./vendor/bin/testlink validate
./vendor/bin/testlink validate --strict
./vendor/bin/testlink validate --json
```

[Full reference →](./validate)

### sync

Synchronize links between production and test code:

```bash
./vendor/bin/testlink sync
./vendor/bin/testlink sync --dry-run
./vendor/bin/testlink sync --prune
```

[Full reference →](./sync)

### pair

Resolve placeholder markers to real references:

```bash
./vendor/bin/testlink pair
./vendor/bin/testlink pair --dry-run
./vendor/bin/testlink pair --placeholder=@name
```

[Full reference →](./pair)

## Output Formats

### Console (default)

Human-readable output with formatting:

```
  Coverage Links Report
  ─────────────────────

  App\Services\UserService
    create()
    → Tests\UserServiceTest::test_creates_user
```

### JSON

Machine-readable output:

```bash
./vendor/bin/testlink report --json
```

```json
{
  "classes": {
    "App\\Services\\UserService": {
      "methods": {
        "create": {
          "tests": ["Tests\\UserServiceTest::test_creates_user"]
        }
      }
    }
  }
}
```

## Exit Codes

| Code | Meaning |
|------|---------|
| `0` | Success (all valid) |
| `1` | Failure (errors found) |

Use exit codes for CI integration:

```bash
./vendor/bin/testlink validate || echo "Validation failed"
```

## Path Filtering

All commands support path filtering:

```bash
# Filter by directory
./vendor/bin/testlink report --path=src/Services

# Filter by file
./vendor/bin/testlink validate --path=src/UserService.php
```

## Verbose Mode

Get detailed output:

```bash
./vendor/bin/testlink report --verbose
./vendor/bin/testlink validate --verbose
```

Verbose mode shows:
- File paths
- Line numbers
- Processing details
