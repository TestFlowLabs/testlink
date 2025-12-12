# @see Tags

TestLink supports `@see` PHPDoc tags as an alternative to PHP 8 attributes for linking production code to tests.

## Overview

The `@see` tag is a standard PHPDoc annotation that creates navigable references between code:

```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
public function create(string $email): User
```

### Why Use @see Tags?

| Feature | @see Tags | PHP 8 Attributes |
|---------|-----------|------------------|
| Class navigation | :white_check_mark: Ctrl+Click | :white_check_mark: Ctrl+Click |
| **Method navigation** | :white_check_mark: **Full** | :x: String only |
| Production dependency | :white_check_mark: None | :x: test-attributes |
| Refactoring support | Manual | Automatic (class only) |
| TestLink validation | :white_check_mark: Yes | :white_check_mark: Yes |

### The Navigation Gap

```php
// @see - FULL NAVIGATION
/** @see \Tests\UserServiceTest::testCreate */
//        ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ Ctrl+Click → goes to METHOD

// #[TestedBy] - PARTIAL NAVIGATION
#[TestedBy(UserServiceTest::class, 'testCreate')]
//         ^^^^^^^^^^^^^^^^^^^^^ Ctrl+Click → goes to CLASS
//                                 ^^^^^^^^^^^^ String - NOT clickable!
```

### Zero Production Dependencies

@see tags are just comments - PHP doesn't parse them at runtime:

::: code-group
```text [With Attributes]
Production Code
├── composer.json:
│   "require": {
│     "testflowlabs/test-attributes": "^1.0"  ← REQUIRED
│   }
│
└── Why? #[TestedBy] class must exist when PHP loads production code
```

```text [With @see Tags]
Production Code
├── composer.json:
│   "require": { }                            ← NO TEST PACKAGES
│   "require-dev": {
│     "testflowlabs/testlink": "^1.0"
│   }
│
└── Why? @see is just a comment, no runtime dependency
```
:::

## PHPUnit Usage (Full Support)

PHPUnit has full @see tag support with bidirectional IDE navigation.

### Production → Test

Add @see tags to production method docblocks:

```php
<?php

namespace App\Services;

class UserService
{
    /**
     * Create a new user.
     *
     * @see \Tests\Unit\UserServiceTest::test_creates_user
     * @see \Tests\Unit\UserServiceTest::test_validates_email
     */
    public function create(string $email): User
    {
        // Implementation
    }
}
```

### Test → Production

Add @see tags above test methods:

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserServiceTest extends TestCase
{
    /**
     * @see \App\Services\UserService::create
     */
    #[Test]
    public function test_creates_user(): void
    {
        // Test implementation
    }
}
```

### Complete Bidirectional Example

::: code-group
```php [Production: UserService.php]
<?php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    /**
     * @see \Tests\Unit\UserServiceTest::test_creates_user
     */
    #[TestedBy(UserServiceTest::class, 'test_creates_user')]
    public function create(string $email): User
    {
        // Implementation
    }
}
```

```php [Test: UserServiceTest.php]
<?php

namespace Tests\Unit;

use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    /**
     * @see \App\Services\UserService::create
     */
    #[LinksAndCovers(UserService::class, 'create')]
    #[Test]
    public function test_creates_user(): void
    {
        // Test implementation
    }
}
```
:::

## Pest Usage (Limitations)

::: warning Pest @see Limitations
Pest has limited @see support due to how test functions work:
- **Test → Production**: Works (place @see above `test()`)
- **Production → Test**: NOT RECOMMENDED (test names have spaces)
- **IDE Navigation**: Does NOT work (function calls ≠ definitions)
:::

### Test → Production (Works)

PHPDoc can be placed above `test()` function calls:

```php
<?php

use App\Services\UserService;

/**
 * @see \App\Services\UserService::create
 */
test('creates a user', function () {
    // Test implementation
})->linksAndCovers(UserService::class.'::create');
```

TestLink can parse this - nikic/php-parser attaches docblocks to the next statement.

### Production → Test (Not Recommended)

Pest test identifiers contain spaces, which are invalid in @see syntax:

```php
// Pest internal identifier:
"Tests\ExampleTest::creates a user"  // Description as pseudo-method

// @see syntax requires valid PHP identifiers:
@see \Tests\ExampleTest::creates a user  // ❌ INVALID - spaces not allowed
```

::: tip Recommendation for Pest
Use PHP 8 attributes (`#[TestedBy]`, `#[LinksAndCovers]`) for Pest projects.
They work perfectly with Pest's test naming conventions.
:::

### Feasibility Matrix

| Direction | PHPUnit | Pest |
|-----------|---------|------|
| Production → Test (@see) | :white_check_mark: Yes | :x: Invalid syntax |
| Test → Production (@see) | :white_check_mark: Yes | :white_check_mark: Yes |
| IDE Navigation | :white_check_mark: Yes | :x: No |
| TestLink Parsing | :white_check_mark: Yes | :white_check_mark: Yes |

### Why No IDE Navigation for Pest?

PHPStorm navigates to **definitions**, not **usages**:

```php
// PHPUnit: @see points to a METHOD DEFINITION
/** @see \Tests\UserServiceTest::testCreate */
//        ↑ PHPStorm finds: public function testCreate()

// Pest: @see points to a FUNCTION CALL
/** @see \Tests\ExampleTest::creates user */
//        ↑ PHPStorm can't find a definition (it's a test() call)
```

## IDE Navigation

### PHPStorm

@see tags are fully clickable in PHPStorm:

1. **Ctrl+Click** (or **Cmd+Click** on Mac) on the reference
2. PHPStorm navigates to the target method

This works for:
- Production → Test references
- Test → Production references
- Cross-file navigation

### VS Code

With PHP Intelephense or similar extensions:
- Class references are navigable
- Method references may require additional configuration

## CLI Integration

### Sync Command

`testlink sync` automatically generates @see tags:

```bash
testlink sync
```

This adds @see tags to production method docblocks alongside `#[TestedBy]` attributes.

See [Sync Command](/sync/sync-command) for details.

### Validate Command

`testlink validate` detects orphan @see tags:

```bash
testlink validate
```

Output with orphan @see:

```
  Validation Report
  ─────────────────

  Orphan @see Tags
  ────────────────

    ⚠ @see \Tests\Unit\OldTest::deleted_test
      in src/Services/UserService.php:45

  Link Summary
    @see tags: 4 (1 orphan)
```

See [Validation](/guide/validation) for details.

### Report Command

`testlink report` includes @see tags in output:

```bash
testlink report
```

Output:

```
  Coverage Links Report
  ─────────────────────

  @see Tags
  ─────────

  Production code → Tests:
    App\Services\UserService::create
      → Tests\Unit\UserServiceTest::test_creates_user

  Test code → Production:
    Tests\Unit\UserServiceTest::test_creates_user
      → App\Services\UserService::create

  Summary
    @see tags: 4
```

### Pruning Orphan @see Tags

Remove orphan @see tags with:

```bash
testlink sync --prune --force
```

## Best Practices

### Framework Recommendations

| Framework | Recommendation |
|-----------|----------------|
| **PHPUnit** | Use @see tags - full IDE navigation, zero prod deps |
| **Pest** | Use attributes - @see has limited support |
| **Mixed** | Use attributes for consistency across frameworks |

### When to Use @see

:white_check_mark: **Use @see when:**
- You need full method navigation in IDE
- You want zero production dependencies
- You're using PHPUnit exclusively
- You're documenting existing code without TestLink

:x: **Avoid @see when:**
- Using Pest (limited support)
- You need automatic refactoring support
- You're starting a new project (attributes are simpler)

### Combining @see with Attributes

You can use both @see tags AND attributes together:

```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
```

This gives you:
- Full IDE method navigation (@see)
- TestLink validation (attributes)
- Automatic sync support (attributes)

### Docblock Placement

@see tags should be placed:
- **Production**: In method docblocks, before @param/@return tags
- **Tests**: Above the test method, in its own docblock

```php
/**
 * Create a new user account.
 *
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 *
 * @param string $email User's email address
 * @return User The created user instance
 */
public function create(string $email): User
```

## Related Documentation

- [TestedBy Attribute](/guide/testedby-attribute) - Production → Test linking
- [Covers Method Helper](/guide/covers-method-helper) - Test → Production linking
- [Validation](/guide/validation) - Link validation
- [Sync Command](/sync/sync-command) - Automatic link synchronization
- [ADR: @see Tag Support](/decisions/see-tag-support) - Design decision record
