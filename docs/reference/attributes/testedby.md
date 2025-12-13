# #[TestedBy]

Declares that a production method is tested by specific test methods.

## Signature

```php
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class TestedBy
{
    public function __construct(
        public string $testClass,
        public ?string $testMethod = null
    ) {}
}
```

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$testClass` | string | Yes | Test class name or placeholder |
| `$testMethod` | string\|null | No | Test method name (required unless placeholder) |

## Usage

### Basic usage

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

### Using class constant

```php
use Tests\UserServiceTest;

#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(array $data): User
```

### Multiple tests

```php
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
#[TestedBy('Tests\UserServiceTest', 'test_creates_user_with_role')]
#[TestedBy('Tests\UserServiceTest', 'test_validates_email')]
public function create(array $data): User
```

### With placeholder

```php
#[TestedBy('@user-create')]
public function create(array $data): User
```

## Target

`#[TestedBy]` can be applied to:

### Methods

```php
class Calculator
{
    #[TestedBy('Tests\CalculatorTest', 'test_adds_numbers')]
    public function add(int $a, int $b): int
    {
        return $a + $b;
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

### Constructors

```php
class Order
{
    #[TestedBy('Tests\OrderTest', 'test_creates_with_items')]
    public function __construct(array $items)
    {
        // ...
    }
}
```

## Test Name Formats

### PHPUnit test methods

```php
// Test method
public function test_creates_user(): void

// TestedBy
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
```

### Pest tests

```php
// Pest test
test('creates user with valid data', function () { });

// TestedBy - use exact test name
#[TestedBy('Tests\UserServiceTest', 'creates user with valid data')]
```

### Pest tests with describe blocks

```php
// Pest test
describe('UserService', function () {
    test('creates user', function () { });
});

// TestedBy - combine describe and test names
#[TestedBy('Tests\UserServiceTest', 'UserService creates user')]
```

## Validation

TestLink validates `#[TestedBy]` attributes:

```bash
./vendor/bin/testlink validate
```

### Valid

- Test class exists
- Test method exists
- Bidirectional link exists (test has `linksAndCovers`)

### Errors

```
✗ Orphan TestedBy:
  App\UserService::create
    → Tests\UserServiceTest::test_old_method (test not found)
```

## Sync Behavior

When running `testlink sync`:

1. TestedBy attributes are read
2. Corresponding test files are found
3. @see tags are added to tests

```php
// Production
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
public function create(): User

// After sync, test gets:
/**
 * @see \App\UserService::create
 */
public function test_creates_user(): void
```

## Best Practices

### Use class constants

```php
// Preferred - refactoring safe
#[TestedBy(UserServiceTest::class, 'test_creates_user')]

// Also valid - but won't update on rename
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
```

### Add during TDD

Add `#[TestedBy]` when writing production code:

```php
// Step 1: Write failing test
test('creates user', function () { })->linksAndCovers(UserService::class.'::create');

// Step 2: Write production code with TestedBy
#[TestedBy('Tests\UserServiceTest', 'creates user')]
public function create(array $data): User
{
    // make test pass
}
```

### Keep synchronized

Run validation regularly:

```bash
./vendor/bin/testlink validate
```

