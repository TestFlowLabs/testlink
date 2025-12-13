# Use @see Tags

`@see` tags are how TestLink enables **Cmd+Click navigation** between tests and production code. They're the links your IDE follows when you click.

## How @see Tags Enable Navigation

`@see` tags are PHPDoc annotations that IDEs recognize as clickable references:

```php
class UserService
{
    /**
     * @see \Tests\UserServiceTest::test_creates_user   ← Cmd+Click to jump
     */
    public function create(array $data): User
    {
        // ...
    }
}
```

When you Cmd+Click (or Ctrl+Click) on the `@see` tag, your IDE jumps directly to that test. This works in both directions—from production to tests, and from tests to production.

## Adding @see Tags

### In production code

```php
/**
 * Creates a new user in the system.
 *
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 * @see \Tests\Unit\UserServiceTest::test_validates_email
 */
public function create(array $data): User
{
    // implementation
}
```

### In test code

```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
{
    // test implementation
}
```

### In Pest tests

```php
/**
 * @see \App\Services\UserService::create
 */
test('creates user with valid data', function () {
    // test implementation
});
```

## @see Tag Syntax

### Class reference

```php
/** @see \App\Services\UserService */
```

### Method reference

```php
/** @see \App\Services\UserService::create */
```

### Property reference

```php
/** @see \App\Models\User::$email */
```

### Multiple references

```php
/**
 * @see \App\Services\UserService::create
 * @see \App\Services\UserService::validate
 * @see \App\Validators\EmailValidator::isValid
 */
```

## FQCN Format

Always use fully qualified class names:

```php
// Correct - fully qualified
/** @see \App\Services\UserService::create */

// Incorrect - may not work in all contexts
/** @see UserService::create */
```

## Generating @see Tags with Pair (@@prefix)

The `pair` command can generate `@see` tags directly using the `@@` prefix on placeholders.

::: warning PHPUnit Only
The `@@` prefix only works with PHPUnit. Pest tests do not support `@see` tags.
:::

### Using @@prefix

Before pairing:
```php
// Production
#[TestedBy('@@user-create')]
public function create(array $data): User { }

// Test (PHPUnit)
#[LinksAndCovers('@@user-create')]
public function testCreatesUser(): void { }
```

Run pair:
```bash
./vendor/bin/testlink pair
```

After pairing:
```php
// Production - @see tag with FQCN
/** @see \Tests\Unit\UserServiceTest::testCreatesUser */
public function create(array $data): User { }

// Test - @see tag with FQCN
/** @see \App\Services\UserService::create */
public function testCreatesUser(): void { }
```

### @@prefix vs @prefix

| Prefix | Result |
|--------|--------|
| `@A` | PHP attributes (`#[TestedBy]`, `#[LinksAndCovers]`) |
| `@@A` | PHPDoc `@see` tags with FQCN |

Choose based on your team's preference:
- Use `@` for attribute-based traceability
- Use `@@` for documentation-style `@see` tags

## Generating @see Tags with Sync

### From #[TestedBy] attributes

If you have `#[TestedBy]` attributes:

```php
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]
public function create(array $data): User
```

Run sync to generate @see tags in tests:

```bash
./vendor/bin/testlink sync
```

Result in test file:
```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
```

### From linksAndCovers()

If you have `linksAndCovers()` in tests:

```php
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

Run sync with --link-only:

```bash
./vendor/bin/testlink sync --link-only
```

Result in production:
```php
/**
 * @see \Tests\UserServiceTest::test_creates_user
 */
public function create(array $data): User
```

## Combining with Attributes

Use both for maximum benefit:

```php
use TestFlowLabs\TestingAttributes\TestedBy;

/**
 * Creates a new user.
 *
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
#[TestedBy('Tests\Unit\UserServiceTest', 'test_creates_user')]
public function create(array $data): User
{
    // implementation
}
```

- `@see` enables Cmd+Click navigation
- `#[TestedBy]` enables validation and sync

## Validating @see Tags

### Check for FQCN issues

```bash
./vendor/bin/testlink validate
```

Output:
```
✗ Found 1 @see tag FQCN issue(s):
  tests/UserServiceTest.php:15
    @see UserService::create (should use FQCN: \App\Services\UserService)
```

### Fix FQCN issues automatically

```bash
# Preview fixes
./vendor/bin/testlink validate --fix --dry-run

# Apply fixes
./vendor/bin/testlink validate --fix
```

TestLink resolves short names using `use` statements in the file:

```php
use Tests\Unit\UserServiceTest;

// Before fix:
/** @see UserServiceTest::creates_user */

// After fix:
/** @see \Tests\Unit\UserServiceTest::creates_user */
```

## Pruning Orphan @see Tags

Remove @see tags that point to non-existent code:

```bash
./vendor/bin/testlink sync --prune
```

This removes:
```php
/**
 * @see \App\Services\DeletedService::method  ← Removed
 * @see \App\Services\UserService::create     ← Kept
 */
```

## @see Tags vs Attributes

`@see` tags are essential for navigation—they're what your IDE clicks on. Attributes provide validation.

| Feature | @see Tags | Attributes |
|---------|-----------|------------|
| Cmd+Click navigation | ✓ | ✗ |
| Validation in CI | Limited | ✓ Full |
| PHP version | All | PHP 8+ |
| Visible in docs | ✓ | ✗ |

### Use both together

For full functionality (navigation + validation), use both:

```php
/**
 * @see \Tests\UserServiceTest::test_creates_user   ← For navigation
 */
#[TestedBy('Tests\UserServiceTest', 'test_creates_user')]  // For validation
public function create(array $data): User
```

TestLink's `sync` command can generate `@see` tags from your attributes automatically.

## Best Practices

### 1. Be consistent

Choose a pattern and stick to it:
- @see only
- Attributes only
- Both

### 2. Use FQCN always

```php
// Always start with backslash
/** @see \Full\Namespace\Class::method */
```

### 3. Keep @see tags updated

Run sync regularly:
```bash
./vendor/bin/testlink sync --prune
```

### 4. Validate in CI

```yaml
- run: ./vendor/bin/testlink validate
```
