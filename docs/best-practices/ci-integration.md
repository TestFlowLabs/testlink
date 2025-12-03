# CI Integration

Automate coverage link validation in your CI/CD pipelines.

## GitHub Actions

### Basic Workflow

```yaml
name: Tests

on:
  push:
    branches: [main, master]
  pull_request:
    branches: [main, master]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: xdebug

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Validate Coverage Links
        run: ./vendor/bin/testlink validate

      - name: Run Tests
        run: ./vendor/bin/pest --coverage  # or ./vendor/bin/phpunit
```

### With Link Report

```yaml
      - name: Generate Coverage Link Report
        run: |
          ./vendor/bin/testlink report --json > coverage-links.json

      - name: Upload Coverage Links Report
        uses: actions/upload-artifact@v4
        with:
          name: coverage-links
          path: coverage-links.json
```

### Sync Check on Pull Requests

```yaml
      - name: Check Sync Status
        run: |
          OUTPUT=$(./vendor/bin/testlink sync --dry-run 2>&1)
          echo "$OUTPUT"
          if echo "$OUTPUT" | grep -q "Found.*attribute(s) to sync"; then
            echo "::warning::Coverage links need syncing. Run: testlink sync"
            exit 1
          fi
```

## GitLab CI

```yaml
stages:
  - validate
  - test

validate-coverage-links:
  stage: validate
  image: php:8.3
  script:
    - composer install --no-interaction
    - ./vendor/bin/testlink validate

run-tests:
  stage: test
  image: php:8.3
  script:
    - composer install --no-interaction
    - ./vendor/bin/pest --coverage  # or ./vendor/bin/phpunit
  needs:
    - validate-coverage-links
```

## CircleCI

```yaml
version: 2.1

jobs:
  test:
    docker:
      - image: cimg/php:8.3
    steps:
      - checkout
      - run:
          name: Install Dependencies
          command: composer install --no-interaction
      - run:
          name: Validate Coverage Links
          command: ./vendor/bin/testlink validate
      - run:
          name: Run Tests
          command: ./vendor/bin/pest --coverage  # or ./vendor/bin/phpunit

workflows:
  test:
    jobs:
      - test
```

## Pre-commit Hooks

### Using Husky (npm)

```json
// package.json
{
  "scripts": {
    "validate-links": "./vendor/bin/testlink validate"
  },
  "husky": {
    "hooks": {
      "pre-commit": "npm run validate-links"
    }
  }
}
```

### Using GrumPHP

```yaml
# grumphp.yml
grumphp:
  tasks:
    shell:
      scripts:
        - ./vendor/bin/testlink validate
```

### Shell Script

```bash
#!/bin/bash
# .git/hooks/pre-commit

echo "Validating coverage links..."
./vendor/bin/testlink validate

if [ $? -ne 0 ]; then
    echo "Coverage link validation failed!"
    exit 1
fi
```

## Scheduled Sync

### Auto-sync in CI

```yaml
name: Sync Coverage Links

on:
  schedule:
    - cron: '0 0 * * *'  # Daily at midnight
  workflow_dispatch:  # Manual trigger

jobs:
  sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install Dependencies
        run: composer install --no-interaction

      - name: Sync Coverage Links
        run: ./vendor/bin/testlink sync

      - name: Check for Changes
        id: changes
        run: |
          if [[ -n $(git status --porcelain) ]]; then
            echo "has_changes=true" >> $GITHUB_OUTPUT
          fi

      - name: Create Pull Request
        if: steps.changes.outputs.has_changes == 'true'
        uses: peter-evans/create-pull-request@v5
        with:
          commit-message: 'chore: sync coverage links'
          title: 'Sync Coverage Links'
          body: 'Automated sync of coverage links from #[TestedBy] attributes.'
          branch: sync-coverage-links
```

## Exit Codes

Use exit codes for CI integration:

| Exit Code | Meaning |
|-----------|---------|
| 0 | Success - All links valid |
| 1 | Validation failed - Issues found |
| 2 | Error - Could not run validation |

```yaml
      - name: Validate Coverage Links
        run: ./vendor/bin/testlink validate
        continue-on-error: false  # Fail the build on validation errors
```

## Reporting

### JSON Output for Processing

```yaml
      - name: Generate Report
        run: ./vendor/bin/testlink report --json > report.json

      - name: Process Report
        run: |
          LINKS=$(jq '.total_links' report.json)
          echo "Total coverage links: $LINKS"
```

### Custom Notifications

```yaml
      - name: Notify on Failure
        if: failure()
        run: |
          curl -X POST $SLACK_WEBHOOK \
            -H 'Content-Type: application/json' \
            -d '{"text":"Coverage link validation failed!"}'
```

## Best Practices

### 1. Fail Fast

Run validation before tests to catch issues early:

```yaml
jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - name: Validate Links
        run: ./vendor/bin/testlink validate

  test:
    needs: validate
    runs-on: ubuntu-latest
    steps:
      - name: Run Tests
        run: ./vendor/bin/pest  # or ./vendor/bin/phpunit
```

### 2. Cache Dependencies

```yaml
      - name: Cache Composer
        uses: actions/cache@v3
        with:
          path: vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('composer.lock') }}
```

### 3. Parallel Jobs

```yaml
jobs:
  validate-links:
    runs-on: ubuntu-latest
    steps:
      - run: ./vendor/bin/testlink validate

  test-unit:
    runs-on: ubuntu-latest
    steps:
      - run: ./vendor/bin/pest --testsuite=unit  # or phpunit

  test-feature:
    runs-on: ubuntu-latest
    steps:
      - run: ./vendor/bin/pest --testsuite=feature  # or phpunit
```

### 4. Required Status Checks

In GitHub, set validation as a required check:

1. Go to Settings > Branches > Branch protection rules
2. Enable "Require status checks to pass"
3. Add "Validate Coverage Links" as required
