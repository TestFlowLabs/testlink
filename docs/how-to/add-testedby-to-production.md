# Add #[TestedBy] to Production Code

This guide shows how to add `#[TestedBy]` attributes to your production code for traceability.

## Prerequisites

- test-attributes package installed (`composer require testflowlabs/test-attributes`)
- Production code you want to annotate

## Basic Usage

### Step 1: Import the attribute

```php
use TestFlowLabs\TestingAttributes\TestedBy;
```

### Step 2: Add to method

```php
class UserService
{
    #[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
    public function create(array $data): User
    {
        // implementation
    }
}
```

## Finding Test Names

### For Pest tests

The test name is the first argument to `test()` or `it()`:

```php
// Test file
test('creates user with valid email', function () {
    // ...
});

// TestedBy reference
#[TestedBy('Tests\UserServiceTest', 'creates user with valid email')]
```

### For Pest tests with describe blocks

Combine the describe and test names:

```php
// Test file
describe('UserService', function () {
    describe('create', function () {
        test('validates email', function () {
            // ...
        });
    });
});

// TestedBy reference - nested describes are joined with spaces
#[TestedBy('Tests\UserServiceTest', 'UserService create validates email')]
```

### For PHPUnit tests

Use the method name:

```php
// Test file
public function test_creates_user(): void
{
    // ...
}

// TestedBy reference
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
```

## Multiple Test Links

Add multiple attributes for methods tested by several tests:

```php
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
#[TestedBy('Tests\UserServiceTest', 'test_creates_user_with_role')]
#[TestedBy('Tests\Integration\UserFlowTest', 'test_complete_registration')]
public function create(array $data): User
{
    // implementation
}
```

## Using Test Class Constants

For better refactoring support, use class constants:

```php
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(array $data): User
{
    // implementation
}
```

This way, if the test class is renamed, your IDE will update the reference.

## Auto-generating with Sync

Instead of manually adding `#[TestedBy]`, you can:

### Step 1: Add links in tests first

```php
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

### Step 2: Run sync with link-only

```bash
./vendor/bin/testlink sync --link-only
```

This adds @see tags (or can be configured to add #[TestedBy]).

## Validation

After adding attributes, validate:

```bash
./vendor/bin/testlink validate
```

This checks:
- All `#[TestedBy]` references point to existing tests
- The corresponding tests have links (`->linksAndCovers()` / `#[LinksAndCovers]` / `@see`)

## Common Patterns

### Constructor testing

```php
class Order
{
    #[TestedBy('Tests\OrderTest', 'test_creates_with_items')]
    #[TestedBy('Tests\OrderTest', 'test_rejects_empty_items')]
    public function __construct(array $items)
    {
        // ...
    }
}
```

### Static methods

```php
class Config
{
    #[TestedBy('Tests\ConfigTest', 'test_loads_from_file')]
    public static function load(string $path): self
    {
        // ...
    }
}
```

### Private methods (via public interface)

Private methods are tested through their public callers:

```php
class Calculator
{
    #[TestedBy('Tests\CalculatorTest', 'test_calculates_with_precision')]
    public function calculate(float $value): float
    {
        return $this->round($value); // private method
    }

    // No TestedBy on private method - tested via calculate()
    private function round(float $value): float
    {
        // ...
    }
}
```

## Best Practices

1. **Add attributes during TDD** - Add `#[TestedBy]` when writing the production code
2. **Use class references** - `UserServiceTest::class` instead of `'Tests\UserServiceTest'`
3. **Keep in sync** - Run `testlink validate` regularly
4. **Don't over-annotate** - Not every method needs `#[TestedBy]`
