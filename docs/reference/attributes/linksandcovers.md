# #[LinksAndCovers]

Declares that a test method covers and links to specific production methods.

## Signature

```php
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class LinksAndCovers
{
    public function __construct(
        public string $class,
        public ?string $method = null
    ) {}
}
```

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$class` | string | Yes | Production class name or placeholder |
| `$method` | string\|null | No | Method name (null for class-level) |

## Usage

### Basic usage

```php
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_user(): void
    {
        $service = new UserService();
        $user = $service->create(['name' => 'John']);

        $this->assertInstanceOf(User::class, $user);
    }
}
```

### Multiple methods

```php
#[LinksAndCovers(UserService::class, 'create')]
#[LinksAndCovers(UserValidator::class, 'validateEmail')]
public function test_creates_user_with_validation(): void
{
    // Test covers both methods
}
```

### With placeholder

```php
#[LinksAndCovers('@user-create')]
public function test_creates_user(): void
{
    // Placeholder resolved with `testlink pair`
}
```

### Class-level coverage

```php
#[LinksAndCovers(UserService::class)]  // No method = entire class
public function test_user_service_integration(): void
{
    // Covers entire UserService class
}
```

## Target

`#[LinksAndCovers]` can be applied to:

### Test methods

```php
class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_user(): void
    {
        // ...
    }
}
```

### Test classes

```php
#[LinksAndCovers(UserService::class)]
class UserServiceTest extends TestCase
{
    public function test_creates_user(): void
    {
        // All tests in this class cover UserService
    }
}
```

## vs #[Links]

| Attribute | Creates Link | Includes Coverage |
|-----------|-------------|-------------------|
| `#[LinksAndCovers]` | ✓ | ✓ |
| `#[Links]` | ✓ | ✗ |

Use `#[LinksAndCovers]` for:
- Unit tests
- Tests that should count toward coverage

Use `#[Links]` for:
- Integration tests
- E2E tests
- Tests where coverage is handled elsewhere

## Pest Equivalent

`#[LinksAndCovers]` is the PHPUnit equivalent of Pest's `->linksAndCovers()`:

```php
// PHPUnit
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void { }

// Pest equivalent
test('creates user', function () { })
    ->linksAndCovers(UserService::class.'::create');
```

## Validation

TestLink validates `#[LinksAndCovers]` attributes:

```bash
./vendor/bin/testlink validate
```

### Checks

- Class exists
- Method exists (if specified)
- Corresponding `#[TestedBy]` exists in production

### Errors

```
✗ Invalid LinksAndCovers:
  Tests\UserServiceTest::test_creates_user
    → App\UserService::nonexistent (method not found)
```

## Sync Behavior

When running `testlink sync --link-only`:

1. LinksAndCovers attributes are read
2. Corresponding production files are found
3. @see tags are added to production methods

```php
// Test
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void

// After sync, production gets:
/**
 * @see \Tests\UserServiceTest::test_creates_user
 */
public function create(array $data): User
```

## Method Reference Format

### Full namespace

```php
#[LinksAndCovers('App\Services\UserService', 'create')]
```

### Using ::class

```php
use App\Services\UserService;

#[LinksAndCovers(UserService::class, 'create')]
```

### Placeholder

```php
#[LinksAndCovers('@user-create')]
```

## Best Practices

### Use ::class constant

```php
// Preferred
#[LinksAndCovers(UserService::class, 'create')]

// Works but less maintainable
#[LinksAndCovers('App\Services\UserService', 'create')]
```

### One attribute per method

```php
// Clear and explicit
#[LinksAndCovers(UserService::class, 'create')]
#[LinksAndCovers(UserService::class, 'validate')]
public function test_creates_validated_user(): void

// Avoid: class-level when method-level is more accurate
#[LinksAndCovers(UserService::class)]  // Too broad
public function test_creates_user(): void
```

### Match with TestedBy

Ensure bidirectional linking:

```php
// Production
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(array $data): User

// Test
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
```

