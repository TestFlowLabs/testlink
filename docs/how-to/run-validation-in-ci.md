# Run Validation in CI

This guide shows how to integrate TestLink validation into your CI/CD pipeline.

## Basic CI Integration

### Step 1: Add validation command

Add the validate command to your CI script:

```bash
./vendor/bin/testlink validate
```

The command returns:
- Exit code `0` - All links valid
- Exit code `1` - Validation errors found

### Step 2: Configure strictness

For stricter validation:

```bash
./vendor/bin/testlink validate --strict
```

This fails on warnings as well as errors.

## GitHub Actions

### Basic workflow

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --no-interaction

      - name: Run tests
        run: ./vendor/bin/pest

      - name: Validate TestLink
        run: ./vendor/bin/testlink validate
```

### With JSON output

```yaml
      - name: Validate TestLink
        run: ./vendor/bin/testlink validate --json > testlink-report.json

      - name: Upload report
        uses: actions/upload-artifact@v4
        with:
          name: testlink-report
          path: testlink-report.json
```

### Separate validation job

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: ./vendor/bin/pest

  testlink:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: ./vendor/bin/testlink validate --strict
```

## GitLab CI

```yaml
# .gitlab-ci.yml
stages:
  - test

test:
  stage: test
  image: php:8.2
  before_script:
    - composer install --no-interaction
  script:
    - ./vendor/bin/pest
    - ./vendor/bin/testlink validate

testlink:report:
  stage: test
  image: php:8.2
  before_script:
    - composer install --no-interaction
  script:
    - ./vendor/bin/testlink report --json > testlink-report.json
  artifacts:
    paths:
      - testlink-report.json
    expire_in: 1 week
```

## CircleCI

```yaml
# .circleci/config.yml
version: 2.1

jobs:
  test:
    docker:
      - image: cimg/php:8.2
    steps:
      - checkout
      - run: composer install
      - run: ./vendor/bin/pest
      - run: ./vendor/bin/testlink validate

workflows:
  test:
    jobs:
      - test
```

## Pre-commit Hook

### Using git hooks directly

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash

echo "Running TestLink validation..."
./vendor/bin/testlink validate

if [ $? -ne 0 ]; then
    echo "TestLink validation failed. Please fix the issues before committing."
    exit 1
fi
```

Make it executable:

```bash
chmod +x .git/hooks/pre-commit
```

### Using Husky (for Node.js projects)

```json
// package.json
{
  "husky": {
    "hooks": {
      "pre-commit": "./vendor/bin/testlink validate"
    }
  }
}
```

### Using CaptainHook (PHP)

```json
// captainhook.json
{
  "pre-commit": {
    "actions": [
      {
        "action": "./vendor/bin/testlink validate"
      }
    ]
  }
}
```

## Scheduled Validation

Run comprehensive reports on a schedule:

### GitHub Actions (weekly)

```yaml
name: Weekly TestLink Report

on:
  schedule:
    - cron: '0 0 * * 0' # Every Sunday at midnight

jobs:
  report:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: ./vendor/bin/testlink report --json > report.json
      - run: ./vendor/bin/testlink validate --json > validate.json
      - uses: actions/upload-artifact@v4
        with:
          name: testlink-reports
          path: |
            report.json
            validate.json
```

## Combining with Other Tools

### With PHPStan

```yaml
      - name: Static Analysis
        run: |
          ./vendor/bin/phpstan analyse
          ./vendor/bin/testlink validate
```

### With Pest coverage

```yaml
      - name: Tests with Coverage
        run: ./vendor/bin/pest --coverage --min=80

      - name: TestLink Validation
        run: ./vendor/bin/testlink validate
```

## Handling Failures

### Allow warnings, fail on errors

```bash
# Exits 0 for warnings, 1 for errors
./vendor/bin/testlink validate
```

### Fail on any issue

```bash
# Exits 1 for warnings AND errors
./vendor/bin/testlink validate --strict
```

### Continue on failure (for non-blocking checks)

```yaml
      - name: TestLink Validation
        run: ./vendor/bin/testlink validate
        continue-on-error: true
```

## Best Practices

1. **Run on every PR** - Catch broken links early
2. **Use strict mode in CI** - Be more lenient locally
3. **Generate reports** - Archive for trend analysis
4. **Block merges on failure** - Require validation to pass
5. **Add to PR checks** - Make status visible to reviewers
