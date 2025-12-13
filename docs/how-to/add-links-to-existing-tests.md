# Add Links to Existing Tests

This guide shows how to add TestLink links to tests that already exist in your project.

## Prerequisites

- TestLink and test-attributes installed
- Existing test files (Pest or PHPUnit)

## For Pest Tests

### Step 1: Identify the test

Find the test you want to link:

```php
test('creates a new user', function () {
    $service = new UserService();
    $user = $service->create(['name' => 'John']);

    expect($user->name)->toBe('John');
});
```

### Step 2: Add Test-Side Link

Add a link to your test (Pest example shown, see tabs for PHPUnit alternatives):

```php
test('creates a new user', function () {
    $service = new UserService();
    $user = $service->create(['name' => 'John']);

    expect($user->name)->toBe('John');
})->linksAndCovers(UserService::class.'::create');
```

### Step 3: For describe blocks

For tests inside describe blocks, add the chain after the closing parenthesis:

```php
describe('UserService', function () {
    test('creates a new user', function () {
        // ...
    })->linksAndCovers(UserService::class.'::create');

    test('updates a user', function () {
        // ...
    })->linksAndCovers(UserService::class.'::update');
});
```

## For PHPUnit Tests

### Step 1: Import the attribute

Add the import at the top of your test file:

```php
use TestFlowLabs\TestingAttributes\LinksAndCovers;
```

### Step 2: Add the attribute

Add `#[LinksAndCovers]` above the test method:

```php
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_a_new_user(): void
{
    $service = new UserService();
    $user = $service->create(['name' => 'John']);

    $this->assertSame('John', $user->name);
}
```

### Step 3: For multiple methods

Add multiple attributes for tests covering multiple methods:

```php
#[LinksAndCovers(UserService::class, 'create')]
#[LinksAndCovers(UserValidator::class, 'validate')]
public function test_creates_validated_user(): void
{
    // ...
}
```

## Bulk Adding with Sync

If you have many tests, use the sync command:

### Step 1: Add #[TestedBy] to production code first

```php
class UserService
{
    #[TestedBy('Tests\UserServiceTest', 'test_creates_a_new_user')]
    public function create(array $data): User
    {
        // ...
    }
}
```

### Step 2: Run sync

```bash
./vendor/bin/testlink sync
```

This automatically adds the corresponding test links (`->linksAndCovers()` for Pest, `@see` tags for PHPUnit).

### Step 3: Preview first (recommended)

```bash
./vendor/bin/testlink sync --dry-run
```

## Using links() for Integration Tests

For integration tests that shouldn't affect code coverage:

:::tabs key:stack
== Pest

```php
test('complete checkout flow', function () {
    // Integration test
})->links(CheckoutService::class.'::process');
```

== PHPUnit + Attributes

```php
use TestFlowLabs\TestingAttributes\Links;

#[Links(CheckoutService::class, 'process')]
public function test_complete_checkout_flow(): void
{
    // Integration test
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\CheckoutService::process
 */
public function test_complete_checkout_flow(): void
{
    // Integration test
}
```

::: warning
Using @see tags for integration tests provides traceability but does not affect code coverage metrics.
:::

:::

## Verify Your Links

After adding links, verify them:

```bash
./vendor/bin/testlink validate
```

## Common Patterns

### One test, one method

```php
test('calculates total')
    ->linksAndCovers(Calculator::class.'::calculate');
```

### One test, multiple methods

```php
test('processes order')
    ->linksAndCovers(OrderService::class.'::validate')
    ->linksAndCovers(OrderService::class.'::create')
    ->linksAndCovers(OrderService::class.'::notify');
```

### Multiple tests, one method

```php
test('returns sum for positive numbers')
    ->linksAndCovers(Calculator::class.'::add');

test('returns sum for negative numbers')
    ->linksAndCovers(Calculator::class.'::add');

test('handles zero')
    ->linksAndCovers(Calculator::class.'::add');
```
