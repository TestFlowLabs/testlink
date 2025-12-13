# Attributes Overview

TestLink provides PHP 8 attributes for declaring test-to-code links.

## Package

All attributes are in the `testflowlabs/test-attributes` package:

```bash
composer require testflowlabs/test-attributes
```

## Available Attributes

| Attribute | Usage | Package |
|-----------|-------|---------|
| `#[TestedBy]` | Production code | test-attributes |
| `#[LinksAndCovers]` | Test code | test-attributes |
| `#[Links]` | Test code | test-attributes |

## Namespace

```php
use TestFlowLabs\TestingAttributes\TestedBy;
use TestFlowLabs\TestingAttributes\LinksAndCovers;
use TestFlowLabs\TestingAttributes\Links;
```

## Quick Reference

### #[TestedBy]

Placed on production methods to declare which tests verify them:

```php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
    public function create(array $data): User
    {
        // ...
    }
}
```

[Full reference →](./testedby)

### #[LinksAndCovers]

Placed on test methods to declare which production methods they test (with coverage):

```php
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_user(): void
    {
        // ...
    }
}
```

[Full reference →](./linksandcovers)

### #[Links]

Placed on test methods for traceability without affecting coverage:

```php
use TestFlowLabs\TestingAttributes\Links;

class UserFlowTest extends TestCase
{
    #[Links(UserService::class, 'create')]
    public function test_complete_registration_flow(): void
    {
        // Integration test
    }
}
```

[Full reference →](./links)

## Attribute Targets

| Attribute | Valid Targets |
|-----------|---------------|
| `#[TestedBy]` | Methods, Functions |
| `#[LinksAndCovers]` | Methods, Classes |
| `#[Links]` | Methods, Classes |

## Multiple Attributes

All attributes can be repeated:

```php
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
#[TestedBy('Tests\UserServiceTest', 'test_validates_email')]
#[TestedBy('Tests\Integration\UserFlowTest', 'test_registration')]
public function create(array $data): User
```

## Class Constants

Use `::class` for type-safe references:

```php
// Recommended
#[TestedBy(UserServiceTest::class, 'test_creates_user')]

// Also valid
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
```

## Placeholder Support

All attributes support placeholder syntax:

```php
// Placeholder - resolved later with `testlink pair`
#[TestedBy('@user-create')]

// Real reference
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
```

## Validation

TestLink validates all attributes:

```bash
./vendor/bin/testlink validate
```

Checks:
- References point to existing code
- Bidirectional links match
- No orphaned attributes
