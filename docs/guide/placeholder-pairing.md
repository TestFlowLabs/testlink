# Placeholder Pairing

Placeholder pairing allows you to use temporary markers during rapid TDD/BDD development, then resolve them to real class references later.

## What are Placeholders?

During rapid development, writing full class references for every test link is tedious:

```php
// Production - verbose
#[TestedBy('Tests\Unit\UserServiceTest', 'it creates a user')]

// Test - verbose
->linksAndCovers(UserService::class.'::create')
```

Placeholders let you use short markers like `@A` or `@user-create`:

```php
// Production - simple
#[TestedBy('@A')]

// Test - simple
->linksAndCovers('@A')
```

When you're ready, run `testlink pair` to resolve all placeholders into real references.

## Why Use Placeholders?

- **Speed**: Focus on writing code, not remembering class paths
- **Flexibility**: Rename classes without updating placeholders
- **Iteration**: Perfect for rapid TDD cycles
- **Temporary Links**: Establish connections before finalizing structure

## Placeholder Syntax

Placeholders must:
- Start with `@` followed by a letter
- Contain only letters, numbers, underscores, and hyphens

**Valid placeholders:**
- `@A`, `@B`, `@C` - Single letters for quick iteration
- `@user-create` - Descriptive names
- `@UserCreate123` - Mixed case with numbers
- `@test_helper` - Underscores allowed

**Invalid placeholders:**
- `@` - Missing identifier
- `@123` - Cannot start with number
- `@invalid!` - Special characters not allowed

## Using Placeholders

### In Production Code

Use `#[TestedBy]` with a placeholder:

```php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy('@user-create')]
    public function create(array $data): User
    {
        // ...
    }

    #[TestedBy('@A')]
    #[TestedBy('@B')]
    public function update(User $user, array $data): User
    {
        // Multiple placeholders on same method
    }
}
```

### In Test Code - Pest

Use `linksAndCovers()` or `links()` with a placeholder:

```php
test('creates a user', function () {
    // ...
})->linksAndCovers('@user-create');

test('validates user email', function () {
    // ...
})->linksAndCovers('@user-create');

describe('UserService', function () {
    test('updates user', function () {
        // ...
    })->linksAndCovers('@A');
});
```

### In Test Code - PHPUnit

Use `#[LinksAndCovers]` or `#[Links]` attributes with a placeholder:

```php
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers('@user-create')]
    public function test_creates_user(): void
    {
        // ...
    }

    #[LinksAndCovers('@A')]
    #[LinksAndCovers('@B')]
    public function test_updates_user(): void
    {
        // Multiple placeholders
    }
}
```

## N:M Matching

The same placeholder creates links between **all** matching production methods and **all** matching tests.

**Example:** If you have:
- 2 production methods with `#[TestedBy('@A')]`
- 3 tests with `->linksAndCovers('@A')`

Result: **6 links** (2 × 3 = 6)

```php
// Production: 2 methods
class UserService
{
    #[TestedBy('@A')]
    public function create(): void { }

    #[TestedBy('@A')]
    public function update(): void { }
}

// Tests: 3 tests
test('creates user', fn() => ...)->linksAndCovers('@A');
test('validates user', fn() => ...)->linksAndCovers('@A');
test('stores user', fn() => ...)->linksAndCovers('@A');

// After pairing: each method links to all 3 tests
// create() → 3 tests
// update() → 3 tests
// Total: 6 links
```

## Running testlink pair

### Preview Changes (Dry Run)

Always preview changes first:

```bash
testlink pair --dry-run
```

Output:

```
  Pairing Placeholders
  ────────────────────

  Running in dry-run mode. No files will be modified.

  Scanning for placeholders...

  Found Placeholders
  ──────────────────

    ✓ @user-create  1 production × 2 tests = 2 links
    ✓ @A  2 production × 3 tests = 6 links
    ✗ @orphan  1 production × 0 tests = 0 links

  Production Files
  ────────────────

    src/Services/UserService.php
      @user-create → UserServiceTest::it creates a user
      @user-create → UserServiceTest::it validates user

  Test Files
  ──────────

    tests/Unit/UserServiceTest.php
      @user-create → UserService::create

  Dry run complete. Would modify 2 file(s) with 8 change(s).

    Run without --dry-run to apply changes:
    testlink pair
```

### Apply Changes

Once satisfied with the preview:

```bash
testlink pair
```

Output:

```
  Pairing Placeholders
  ────────────────────

  Scanning for placeholders...

  Found Placeholders
  ──────────────────

    ✓ @user-create  1 production × 2 tests = 2 links

  Production Files
  ────────────────

    src/Services/UserService.php
      @user-create → UserServiceTest::it creates a user

  Test Files
  ──────────

    tests/Unit/UserServiceTest.php
      @user-create → UserService::create

  ✓ Pairing complete. Modified 2 file(s) with 2 change(s).
```

### Resolve Specific Placeholder

To resolve only one placeholder:

```bash
testlink pair --placeholder=@user-create
```

This is useful when you want to finalize one feature while keeping others as placeholders.

## Error Handling

### Orphan Production Placeholder

When a placeholder exists only in production code (no matching test):

```
  Found Placeholders
  ──────────────────

    ✗ @orphan  1 production × 0 tests = 0 links

  Errors
  ──────

    ✗ Placeholder @orphan has no matching test entries
```

**Solution:** Add a test with `->linksAndCovers('@orphan')` or `#[LinksAndCovers('@orphan')]`

### Orphan Test Placeholder

When a placeholder exists only in test code (no matching production):

```
  Found Placeholders
  ──────────────────

    ✗ @missing  0 production × 2 tests = 0 links

  Errors
  ──────

    ✗ Placeholder @missing has no matching production entries
```

**Solution:** Add `#[TestedBy('@missing')]` to the production method

### Invalid Placeholder Format

```bash
testlink pair --placeholder=invalid
```

```
  ✗ Invalid placeholder format: invalid
```

**Solution:** Ensure placeholder starts with `@` followed by a letter

## Complete Example

### Step 1: Start with Placeholders

During TDD, quickly establish links:

```php
// src/Services/OrderService.php
class OrderService
{
    #[TestedBy('@order')]
    public function create(array $items): Order
    {
        // TODO: implement
    }

    #[TestedBy('@order')]
    public function calculate(Order $order): float
    {
        // TODO: implement
    }
}
```

```php
// tests/Unit/OrderServiceTest.php
test('creates order from items', function () {
    // ...
})->linksAndCovers('@order');

test('calculates order total', function () {
    // ...
})->linksAndCovers('@order');

test('applies discount to order', function () {
    // ...
})->linksAndCovers('@order');
```

### Step 2: Preview Resolution

```bash
testlink pair --dry-run
```

```
  Found Placeholders
  ──────────────────

    ✓ @order  2 production × 3 tests = 6 links
```

### Step 3: Apply Changes

```bash
testlink pair
```

### Step 4: Verify Results

After pairing, your code becomes:

```php
// src/Services/OrderService.php
class OrderService
{
    #[TestedBy('Tests\Unit\OrderServiceTest', 'it creates order from items')]
    #[TestedBy('Tests\Unit\OrderServiceTest', 'it calculates order total')]
    #[TestedBy('Tests\Unit\OrderServiceTest', 'it applies discount to order')]
    public function create(array $items): Order
    {
        // ...
    }

    #[TestedBy('Tests\Unit\OrderServiceTest', 'it creates order from items')]
    #[TestedBy('Tests\Unit\OrderServiceTest', 'it calculates order total')]
    #[TestedBy('Tests\Unit\OrderServiceTest', 'it applies discount to order')]
    public function calculate(Order $order): float
    {
        // ...
    }
}
```

```php
// tests/Unit/OrderServiceTest.php
test('creates order from items', function () {
    // ...
})->linksAndCovers(OrderService::class.'::create')
  ->linksAndCovers(OrderService::class.'::calculate');

test('calculates order total', function () {
    // ...
})->linksAndCovers(OrderService::class.'::create')
  ->linksAndCovers(OrderService::class.'::calculate');

test('applies discount to order', function () {
    // ...
})->linksAndCovers(OrderService::class.'::create')
  ->linksAndCovers(OrderService::class.'::calculate');
```

## Best Practices

1. **Use descriptive placeholders** for complex features: `@user-registration` instead of `@A`
2. **Use single letters** for quick iteration: `@A`, `@B` during initial TDD
3. **Run dry-run first** to verify expected changes
4. **Resolve incrementally** with `--placeholder=@X` for large codebases
5. **Commit before pairing** so you can easily revert if needed

## Workflow Integration

Placeholder pairing fits naturally into TDD/BDD workflows:

1. **Write test** with placeholder: `->linksAndCovers('@feature')`
2. **Write production** with placeholder: `#[TestedBy('@feature')]`
3. **Iterate** until feature is complete
4. **Run** `testlink pair` to finalize links
5. **Commit** the resolved code

See the [TDD Workflow](/workflow/tdd) and [BDD Workflow](/workflow/bdd) guides for detailed examples.
