# Understanding Reports

This tutorial teaches you how to read and interpret TestLink's report output. Understanding reports helps you assess test coverage and identify gaps.

## The Report Command

Run the report command to see all coverage links:

```bash
./vendor/bin/testlink report
```

## Report Structure

A typical report looks like this:

```
  Coverage Links Report
  ─────────────────────

  App\Services\UserService
    create()
    → Tests\Unit\UserServiceTest::test_creates_a_new_user
    → Tests\Unit\UserServiceTest::test_validates_user_email

    update()
    → Tests\Unit\UserServiceTest::test_updates_existing_user

    delete()
    (no tests linked)

  App\Services\OrderService
    process()
    → Tests\Unit\OrderServiceTest::test_processes_order

  Summary
    Methods with tests: 3
    Methods without tests: 1
    Total test links: 4
```

Let's break down each section.

## Class Grouping

Reports are organized by class:

```
  App\Services\UserService        ← Fully qualified class name

    create()                      ← Method name
    → Tests\Unit\UserServiceTest::test_creates_a_new_user
                                  ↑ Arrow indicates "tested by"
```

## Method Coverage

Each method shows its linked tests:

```
    create()
    → Tests\Unit\UserServiceTest::test_creates_a_new_user
    → Tests\Unit\UserServiceTest::test_validates_user_email
```

This means `create()` is covered by two tests.

## Methods Without Tests

Methods with no linked tests are clearly marked:

```
    delete()
    (no tests linked)
```

This helps identify untested code.

## Summary Section

The summary gives you a quick overview:

```
  Summary
    Methods with tests: 3      ← Methods that have at least one test
    Methods without tests: 1   ← Methods with no linked tests
    Total test links: 4        ← Total number of test→method links
```

## JSON Output

For CI/CD integration, use JSON output:

```bash
./vendor/bin/testlink report --json
```

```json
{
  "classes": {
    "App\\Services\\UserService": {
      "methods": {
        "create": {
          "tests": [
            "Tests\\Unit\\UserServiceTest::test_creates_a_new_user",
            "Tests\\Unit\\UserServiceTest::test_validates_user_email"
          ]
        },
        "update": {
          "tests": [
            "Tests\\Unit\\UserServiceTest::test_updates_existing_user"
          ]
        },
        "delete": {
          "tests": []
        }
      }
    }
  },
  "summary": {
    "methodsWithTests": 3,
    "methodsWithoutTests": 1,
    "totalLinks": 4
  }
}
```

## Filtering Reports

### By Path

Focus on specific directories:

```bash
./vendor/bin/testlink report --path=src/Services
```

### Verbose Mode

Get more details with verbose output:

```bash
./vendor/bin/testlink report --verbose
```

This shows:
- File paths
- Line numbers
- Link sources (attribute vs method chain)

## Reading Validation Reports

The validate command provides a different view:

```bash
./vendor/bin/testlink validate
```

```
  Validation Report
  ─────────────────

  Link Summary
    PHPUnit attribute links: 10
    Pest method chain links: 5
    @see tags: 0
    Total links: 15

  ✗ Found 2 orphan TestedBy link(s):
    App\UserService::create
      → Tests\UserServiceTest::test_old_test (test not found)
    App\UserService::update
      → Tests\UserServiceTest::test_removed (test not found)
```

### Validation Issues

| Issue | Meaning | Solution |
|-------|---------|----------|
| Orphan TestedBy | `#[TestedBy]` exists but no matching test link | Add test link (`->linksAndCovers()` / `#[LinksAndCovers]` / `@see`) or remove `#[TestedBy]` |
| Missing TestedBy | Test has link but no `#[TestedBy]` | Add `#[TestedBy]` to production code or run `sync` |
| Duplicate links | Same link appears in both attribute and method chain | Remove one of the duplicates |

## Understanding Link Sources

TestLink finds links from multiple sources:

1. **PHPUnit attributes** - `#[LinksAndCovers]` on test methods
2. **Pest method chains** - `->linksAndCovers()` on tests
3. **TestedBy attributes** - `#[TestedBy]` on production methods

The report combines all sources into a unified view.

## Common Patterns

### Well-Covered Method

```
    validateEmail()
    → Tests\Unit\ValidatorTest::test_validates_correct_email
    → Tests\Unit\ValidatorTest::test_rejects_invalid_email
    → Tests\Unit\ValidatorTest::test_handles_empty_string
```

Multiple tests cover different scenarios.

### Single Test Coverage

```
    getVersion()
    → Tests\Unit\AppTest::test_returns_correct_version
```

Simple methods may only need one test.

### Integration Test Links

```
    processPayment()
    → Tests\Integration\PaymentFlowTest::test_complete_payment_flow
    → Tests\Unit\PaymentServiceTest::test_validates_payment_data
```

Methods can be covered by both unit and integration tests.

## Best Practices

1. **Review reports regularly** - Run reports as part of your workflow
2. **Address "(no tests linked)" entries** - These highlight potential coverage gaps
3. **Use JSON for automation** - Parse JSON output in CI pipelines
4. **Filter by path** - Focus on specific areas when reviewing

## What's Next?

Now that you understand reports:

- [Run Validation in CI](/how-to/run-validation-in-ci) - Automate validation
- [TDD Workflow](./tdd/) - Learn when to add links during development
- [Fix Validation Errors](/how-to/fix-validation-errors) - Resolve common issues
