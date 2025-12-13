# Migrate Existing Project

This guide shows how to add TestLink to an existing project with pre-existing tests.

## Overview

Migration involves:
1. Installing packages
2. Identifying critical code to link
3. Adding links incrementally
4. Setting up validation

## Step 1: Install Packages

```bash
# Production dependency - attributes
composer require testflowlabs/test-attributes

# Dev dependency - CLI tools
composer require --dev testflowlabs/testlink
```

## Step 2: Verify Installation

```bash
./vendor/bin/testlink report
```

You should see an empty report (no links exist yet):

```
Coverage Links Report
─────────────────────

No coverage links found.
```

## Step 3: Choose a Migration Strategy

### Strategy A: Top-Down (Recommended)

Start with the most critical production code:

1. Identify core business logic
2. Add `#[TestedBy]` to those methods
3. Run sync to add test links
4. Expand gradually

### Strategy B: Bottom-Up

Start with tests:

1. Add `linksAndCovers()` to existing tests
2. Run sync to add production links
3. Validate and expand

### Strategy C: Feature-by-Feature

Add links when working on features:

1. When you touch a file, add its links
2. New code always gets links
3. Eventually full coverage

## Step 4: Start Linking (Top-Down Example)

### 4.1 Identify critical classes

Choose your most important classes:

```
src/
├── Services/
│   ├── UserService.php      ← Start here
│   ├── OrderService.php     ← Then here
│   └── PaymentService.php   ← Then here
```

### 4.2 Add attributes to production

```php
<?php
// src/Services/UserService.php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy('Tests\Unit\UserServiceTest', 'test_creates_user')]
    #[TestedBy('Tests\Unit\UserServiceTest', 'test_validates_email')]
    public function create(array $data): User
    {
        // existing implementation
    }

    #[TestedBy('Tests\Unit\UserServiceTest', 'test_finds_user_by_id')]
    public function find(int $id): ?User
    {
        // existing implementation
    }
}
```

### 4.3 Run sync to update tests

```bash
# Preview changes
./vendor/bin/testlink sync --dry-run

# Apply changes
./vendor/bin/testlink sync
```

### 4.4 Validate

```bash
./vendor/bin/testlink validate
```

## Step 5: Handle Common Situations

### Tests without matching methods

If your test names don't match method names:

```php
// Test name
public function test_it_should_create_a_user()

// Doesn't match production method name
// Use the actual test name in TestedBy
#[TestedBy('Tests\UserServiceTest', 'test_it_should_create_a_user')]
```

### Multiple test classes for one production class

```php
#[TestedBy('Tests\Unit\UserServiceTest', 'test_creates_user')]
#[TestedBy('Tests\Integration\UserFlowTest', 'test_user_registration_flow')]
public function create(array $data): User
```

### Legacy tests without clear method mapping

Start with `links()` instead of `linksAndCovers()` for integration tests:

```php
test('legacy integration test', function () {
    // Tests multiple things
})
->links(UserService::class.'::create')
->links(UserService::class.'::validate');
```

## Step 6: Set Up CI

Add validation to your CI pipeline:

```yaml
# .github/workflows/test.yml
- name: TestLink Validation
  run: ./vendor/bin/testlink validate
```

Start with warnings allowed, then enable strict mode:

```yaml
# Phase 1: Allow warnings
- run: ./vendor/bin/testlink validate

# Phase 2 (later): Strict mode
- run: ./vendor/bin/testlink validate --strict
```

## Migration Checklist

### Week 1
- [ ] Install packages
- [ ] Link 1-2 critical classes
- [ ] Run validation locally

### Week 2-4
- [ ] Link remaining core services
- [ ] Add to CI (non-blocking)
- [ ] Document approach for team

### Month 2+
- [ ] Link all new code
- [ ] Gradually link existing code
- [ ] Enable strict CI validation

## Tips for Large Codebases

### Use path filtering

Focus on specific directories:

```bash
./vendor/bin/testlink report --path=src/Services
./vendor/bin/testlink validate --path=src/Services
```

### Track progress

```bash
# Count linked methods
./vendor/bin/testlink report --json | jq '.summary.methodsWithTests'

# Count unlinked methods
./vendor/bin/testlink report --json | jq '.summary.methodsWithoutTests'
```

### Batch linking

For classes with many methods, add links in batches:

```bash
# This week: UserService
# Next week: OrderService
# Week after: PaymentService
```

## Common Mistakes to Avoid

1. **Don't try to link everything at once** - It's overwhelming
2. **Don't skip validation** - Run it regularly
3. **Don't forget integration tests** - Use `links()` for them
4. **Don't ignore existing test names** - Use them exactly as written

## Measuring Success

Track these metrics:

| Metric | Start | Target |
|--------|-------|--------|
| Methods with links | 0 | 80%+ |
| Validation errors | N/A | 0 |
| CI passes | N/A | 100% |
