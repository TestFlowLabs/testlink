# TestedBy Attribute

The `#[TestedBy]` attribute marks production methods as being tested by specific test cases. This creates **bidirectional links** between your production code and tests.

## Why Bidirectional Links?

TestLink supports bidirectional linking:

- **Test → Production**: Tests declare which production code they cover using `linksAndCovers()` or `#[LinksAndCovers]`
- **Production → Test**: Production methods declare which tests verify them using `#[TestedBy]`

When both directions are in sync, validation passes. When they're out of sync, validation reports the mismatches.

## Basic Usage

```php
<?php

namespace App\Services;

use TestFlowLabs\TestLink\Attribute\TestedBy;

class UserService
{
    #[TestedBy('Tests\Unit\UserServiceTest', 'test_creates_user')]
    public function create(string $name, string $email): User
    {
        // Implementation
    }
}
```

## Multiple Tests

A method can be tested by multiple tests:

```php
#[TestedBy('Tests\Unit\UserServiceTest', 'test_creates_user_with_valid_data')]
#[TestedBy('Tests\Unit\UserServiceTest', 'test_fails_when_email_invalid')]
public function create(string $name, string $email): User
{
    // Implementation
}
```

## Linking to Pest Tests

For Pest tests, use the test description:

```php
#[TestedBy('Tests\Unit\UserServiceTest', 'it creates a user')]
public function create(string $name, string $email): User
{
    // Implementation
}
```

## Complete Bidirectional Example

### Production Code

```php
<?php

namespace App\Services;

use TestFlowLabs\TestLink\Attribute\TestedBy;

class OrderService
{
    #[TestedBy('Tests\Unit\OrderServiceTest', 'test_creates_order')]
    public function create(array $items): Order
    {
        // Implementation
    }
}
```

### Test Code (PHPUnit)

```php
<?php

namespace Tests\Unit;

use App\Services\OrderService;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class OrderServiceTest extends TestCase
{
    #[LinksAndCovers(OrderService::class, 'create')]
    public function test_creates_order(): void
    {
        // Test implementation
    }
}
```

### Test Code (Pest)

```php
<?php

use App\Services\OrderService;

test('creates order', function () {
    // Test implementation
})->linksAndCovers(OrderService::class.'::create');
```

## Validation

Run validation to ensure bidirectional links are synchronized:

```bash
testlink validate
```

The validator will report:

- **Missing TestedBy**: Test links to a method but the method has no `#[TestedBy]` pointing back
- **Orphan TestedBy**: Method has `#[TestedBy]` but no test links to it

## Sync

You can automatically sync links in either direction:

```bash
# Generate TestedBy from test links
testlink sync --direction=test-to-prod

# Generate test links from TestedBy
testlink sync --direction=prod-to-test
```

## IDE Support

The `#[TestedBy]` attribute provides:

- Jump from production method to its tests
- See all tests covering a method at a glance
- Autocomplete for test class names

## Best Practices

1. **Keep in sync**: Run `testlink validate` in CI to ensure bidirectional links stay synchronized
2. **Use meaningful test names**: Test method names should describe the behavior being tested
3. **One-to-one correspondence**: Each `#[TestedBy]` should have a matching `linksAndCovers()` or `#[LinksAndCovers]`
