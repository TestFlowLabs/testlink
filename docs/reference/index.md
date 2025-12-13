# Reference

Technical reference documentation for TestLink.

## CLI Commands

| Command | Description |
|---------|-------------|
| [report](./cli/report) | Display coverage links from attributes |
| [validate](./cli/validate) | Check link integrity and synchronization |
| [sync](./cli/sync) | Synchronize links between production and tests |
| [pair](./cli/pair) | Resolve placeholder markers |

[CLI Overview →](./cli/)

## Attributes

| Attribute | Package | Usage |
|-----------|---------|-------|
| [#[TestedBy]](./attributes/testedby) | test-attributes | Production code |
| [#[LinksAndCovers]](./attributes/linksandcovers) | test-attributes | Test code |
| [#[Links]](./attributes/links) | test-attributes | Test code |

[Attributes Overview →](./attributes/)

## API

| Topic | Description |
|-------|-------------|
| [Pest Methods](./pest-methods) | linksAndCovers(), links() chains |
| [Configuration](./configuration) | Configuration options |

## Quick Reference

### Installation

```bash
composer require testflowlabs/test-attributes
composer require --dev testflowlabs/testlink
```

### Basic Commands

```bash
# Show all links
./vendor/bin/testlink report

# Validate links
./vendor/bin/testlink validate

# Sync links
./vendor/bin/testlink sync

# Resolve placeholders
./vendor/bin/testlink pair
```

### Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Error (validation failed, etc.) |

### Common Options

| Option | Description |
|--------|-------------|
| `--dry-run` | Preview without changes |
| `--json` | JSON output format |
| `--verbose` | Detailed output |
| `--path=<dir>` | Filter by path |
