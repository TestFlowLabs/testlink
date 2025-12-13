# How-to Guides

These guides provide step-by-step instructions for accomplishing specific tasks with TestLink. Each guide focuses on solving a particular problem.

## Navigation & Linking

| Guide | Description |
|-------|-------------|
| [IDE Navigation Reference](./setup-ide-navigation) | IDE-specific details for Cmd+Click navigation |
| [Use @see Tags](./use-see-tags) | The tags that enable IDE navigation |
| [Add Links to Existing Tests](./add-links-to-existing-tests) | Add TestLink links to tests that already exist |
| [Add #[TestedBy] to Production](./add-testedby-to-production) | Add traceability to production code |

## Sync & Placeholder

| Guide | Description |
|-------|-------------|
| [Sync Links Automatically](./sync-links-automatically) | Auto-generate missing links |
| [Use Dry-Run Mode](./use-dry-run-mode) | Preview changes before applying |
| [Resolve Placeholders](./resolve-placeholders) | Convert placeholders to real links |
| [Prune Orphan Links](./prune-orphan-links) | Clean up stale links |
| [Handle N:M Relationships](./handle-nm-relationships) | Manage complex link relationships |

## Validation & Maintenance

| Guide | Description |
|-------|-------------|
| [Run Validation in CI](./run-validation-in-ci) | Ensure links stay accurate in CI/CD |
| [Fix Validation Errors](./fix-validation-errors) | Resolve common validation issues |

## Advanced

| Guide | Description |
|-------|-------------|
| [Migrate Existing Project](./migrate-existing-project) | Add TestLink to legacy codebases |
| [Organize Tests with Describe](./organize-tests-with-describe) | Structure Pest tests effectively |
| [Debug Parsing Issues](./debug-parsing-issues) | Troubleshoot common problems |

## How to Use These Guides

1. **Find your task** - Browse the tables above
2. **Follow the steps** - Each guide has numbered steps
3. **Copy the examples** - Code snippets are ready to use
4. **Verify results** - Each guide includes validation steps

## Prerequisites

Most guides assume you have:
- TestLink installed (`composer require --dev testflowlabs/testlink`)
- test-attributes installed (`composer require testflowlabs/test-attributes`)
- Basic understanding of TestLink concepts (see [Tutorials](/tutorials/))
