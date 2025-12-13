# TestLink Manual Testing Guide

This document provides an iterative guide for manual testing of TestLink.

---

## Agent Instructions

**IMPORTANT: Read this section before starting each phase.**

### Test Projects

| Project | Framework | Path |
|---------|-----------|------|
| backend | PHPUnit | `/Users/deligoez/Developer/github/tarfin-labs/backend` |
| event-machine | Pest | `/Users/deligoez/Developer/github/tarfin-labs/event-machine` |

### Testing Rules

1. **Test in both projects** - Run tests in both backend (PHPUnit) and event-machine (Pest)
2. **Never commit** - Do not commit any changes to test projects
3. **Create test files as needed** - You can create files to test features
4. **Clean up after tests** - Remove all traces after completing each phase
5. **Bug handling** - If you find a bug:
   - Create a unit test in the testlink project to confirm the bug
   - After the fix, re-test manually in both projects

### CLI Output Storage

Save CLI outputs to `/docs/cli-outputs/phase-{N}/{project}/` as `.txt` files:

```
docs/cli-outputs/
├── phase-1/
│   ├── backend/
│   │   ├── help.txt
│   │   ├── version.txt
│   │   └── ...
│   ├── event-machine/
│   │   ├── help.txt
│   │   ├── version.txt
│   │   └── ...
│   └── SUMMARY.md
├── phase-2/
│   └── ...
```

**Output file format:**
- First line: The command that produced the output
- Rest: Command output

Example (`help.txt`):
```
vendor/bin/testlink --help

  TestLink dev-master

  Detected frameworks: pest (phpunit compatible)
  ...
```

**Phase summary:**
Create `SUMMARY.md` in each phase's root directory with:
- Test date
- Project table
- Exit codes
- Findings (if any)
- Output file inventory
- Checklist

See `/docs/cli-outputs/fase-1/SUMMARY.md` for format reference.

---

## Phase 1: Basic CLI

**Tasks (4):**

### 1.1 Help and Version

```bash
# Help display
vendor/bin/testlink --help
vendor/bin/testlink -h

# Version display
vendor/bin/testlink --version
vendor/bin/testlink -v
```

### 1.2 Framework Detection

```bash
# Should display detected framework
# event-machine: "pest (phpunit compatible)"
# backend: "phpunit"
vendor/bin/testlink
```

### 1.3 Unknown Command

```bash
# Should show error and exit code 1
vendor/bin/testlink unknown
echo $?  # Should be 1
```

### 1.4 No-Color Mode

```bash
# Should display without ANSI colors
vendor/bin/testlink --no-color
vendor/bin/testlink report --no-color
```

---

## Phase 2: Empty Project Commands

**Tasks (4):**

### 2.1 Report Command

```bash
# Should show "No coverage links found" or empty report
vendor/bin/testlink report
vendor/bin/testlink report --json
```

### 2.2 Validate Command

```bash
# Should succeed (no links = no errors)
vendor/bin/testlink validate
vendor/bin/testlink validate --json
vendor/bin/testlink validate --strict
```

### 2.3 Sync Command

```bash
# Should show "Nothing to sync" or empty
vendor/bin/testlink sync --dry-run
```

### 2.4 Pair Command

```bash
# Should show "No placeholders found"
vendor/bin/testlink pair --dry-run
```

---

## Phase 3: First TestedBy Attribute

**Tasks (4):**

### 3.1 Add TestedBy Attribute

Add to a production method:

```php
// src/Services/UserService.php (or any class)
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'it creates a user')]
    public function create(array $data): User
    {
        // ...
    }
}
```

### 3.2 Report Command

```bash
# Link should appear
vendor/bin/testlink report

# JSON format
vendor/bin/testlink report --json

# Verbose shows more details
vendor/bin/testlink report --verbose

# Path filter works
vendor/bin/testlink report --path=src/Services
```

### 3.3 Validate Command

```bash
# Link count should appear
vendor/bin/testlink validate
vendor/bin/testlink validate --json
```

### 3.4 Sync Command

```bash
# Should show link to add to test file
vendor/bin/testlink sync --dry-run

# Actually add (check test file)
vendor/bin/testlink sync

# Test file should have linksAndCovers (Pest) or #[LinksAndCovers] (PHPUnit)
```

---

## Phase 4: Basic Placeholder Pairing

**Tasks (4):**

### 4.1 Add Placeholders

Production:
```php
// src/Services/OrderService.php
use TestFlowLabs\TestingAttributes\TestedBy;

class OrderService
{
    #[TestedBy('@order-create')]
    public function create(array $items): Order
    {
        // ...
    }
}
```

Test (Pest):
```php
// tests/Unit/OrderServiceTest.php
test('creates an order', function () {
    // ...
})->linksAndCovers('@order-create');
```

### 4.2 Validate with Unresolved Placeholders

```bash
# Should show "Unresolved Placeholders" warning
vendor/bin/testlink validate

# JSON should have unresolvedPlaceholders
vendor/bin/testlink validate --json

# Strict mode should FAIL (exit code 1)
vendor/bin/testlink validate --strict
echo $?  # Should be 1
```

### 4.3 Pair Dry-Run

```bash
# Should show changes to make
# "@order-create  1 production x 1 test = 1 link"
vendor/bin/testlink pair --dry-run
```

### 4.4 Pair and Verify

```bash
# Resolve placeholders
vendor/bin/testlink pair

# Check files:
# - Production: #[TestedBy('@order-create')] -> #[TestedBy(OrderServiceTest::class, 'creates an order')]
# - Test: ->linksAndCovers('@order-create') -> ->linksAndCovers(OrderService::class.'::create')

# Validate should have no warnings now
vendor/bin/testlink validate

# Strict mode should PASS (exit code 0)
vendor/bin/testlink validate --strict
echo $?  # Should be 0
```

---

## Phase 5: N:M Placeholder Matching

**Tasks (3):**

### 5.1 Setup N:M Placeholders

Production (2 methods):
```php
class PaymentService
{
    #[TestedBy('@payment')]
    public function charge(): void { }

    #[TestedBy('@payment')]
    public function refund(): void { }
}
```

Tests (3 tests):
```php
test('charges payment', fn() => ...)->linksAndCovers('@payment');
test('validates payment', fn() => ...)->linksAndCovers('@payment');
test('refunds payment', fn() => ...)->linksAndCovers('@payment');
```

### 5.2 Pair Dry-Run

```bash
# Should show "2 production x 3 tests = 6 links"
vendor/bin/testlink pair --dry-run
```

### 5.3 Pair and Verify

```bash
vendor/bin/testlink pair

# Verify:
# - Each production method has 3 TestedBy attributes
# - Each test has 2 linksAndCovers calls
```

---

## Phase 6: Sync Features

**Tasks (7):**

### 6.1 Forward Sync (Production → Test)

Production has `#[TestedBy]`, sync adds `linksAndCovers()`/`#[LinksAndCovers]` to test:

```php
// Production: src/Services/UserService.php
#[TestedBy(UserServiceTest::class, 'it creates a user')]
public function create(): User { }
```

```bash
# Dry-run shows test file modification
vendor/bin/testlink sync --dry-run

# Output should show:
# Modified Files
#   ✓ tests/Unit/UserServiceTest.php
#     + UserService::create

vendor/bin/testlink sync
```

Verify test file has `->linksAndCovers(UserService::class.'::create')` (Pest) or `#[LinksAndCovers(UserService::class, 'create')]` (PHPUnit).

### 6.2 Reverse Sync (Test → Production)

Test has `linksAndCovers()`/`#[LinksAndCovers]`, sync adds `#[TestedBy]` to production:

Pest:
```php
// tests/Unit/PaymentServiceTest.php
test('processes payment', function () {
    // ...
})->linksAndCovers(PaymentService::class.'::process');
```

PHPUnit:
```php
// tests/Unit/PaymentServiceTest.php
#[LinksAndCovers(PaymentService::class, 'process')]
public function test_processes_payment(): void { }
```

```bash
# Dry-run shows production file modification
vendor/bin/testlink sync --dry-run

# Output should show:
# Would add #[TestedBy] to
#   ✓ PaymentService::process
#     + #[TestedBy] PaymentServiceTest::processes payment

vendor/bin/testlink sync
```

Verify production file has `#[TestedBy(PaymentServiceTest::class, 'processes payment')]` (or test method name).

### 6.3 Bidirectional Sync Display

```bash
# Run sync with both directions
vendor/bin/testlink sync --dry-run
```

Output should show both:
- "Modified Files" (forward: production → test)
- "Would add #[TestedBy] to" (reverse: test → production)

### 6.4 Link-Only Mode

```bash
# Uses links() instead of linksAndCovers()
vendor/bin/testlink sync --link-only --dry-run
vendor/bin/testlink sync --link-only
```

### 6.5 Add Orphan Link

Manually add a link to a deleted method in test file:

```php
test('some test', function () {
    // ...
})->linksAndCovers(DeletedService::class.'::deletedMethod');
```

### 6.6 Prune Without Force

```bash
# Should error without --force
vendor/bin/testlink sync --prune
```

### 6.7 Prune With Force

```bash
# Dry-run shows what will be deleted
vendor/bin/testlink sync --prune --force --dry-run

# Actually delete
vendor/bin/testlink sync --prune --force
```

---

## Phase 7: @see Tag Generation

**Tasks (4):**

### 7.1 Setup and Sync

Add TestedBy to production:
```php
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
{
    // ...
}
```

```bash
vendor/bin/testlink sync
```

### 7.2 Verify @see Tag

Check production file:
```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
```

### 7.3 Report Shows @see

```bash
vendor/bin/testlink report
```

Expected output includes "@see Tags" section.

### 7.4 Validate Shows @see Count

```bash
vendor/bin/testlink validate
```

Expected output includes "@see tags: X".

---

## Phase 8: @see Orphan Detection & Pruning

**Tasks (4):**

### 8.1 Add Invalid @see

Manually add invalid @see (non-existent class):
```php
/**
 * @see \Tests\Unit\DeletedTest::test_old_method
 */
public function create(): User
```

### 8.2 Validate Shows Orphan

```bash
vendor/bin/testlink validate
```

Expected output shows "Orphan @see Tags" section.

### 8.3 Prune Dry-Run

```bash
vendor/bin/testlink sync --prune --force --dry-run
```

### 8.4 Prune and Verify

```bash
vendor/bin/testlink sync --prune --force

# Verify orphan @see removed
```

---

## Phase 9: FQCN Validation

**Tasks (4):**

### 9.1 Add Non-FQCN @see

Manually add @see without leading backslash:
```php
/**
 * @see UserServiceTest::test_creates_user
 */
public function create(): User
```

### 9.2 Validate Shows Non-FQCN

```bash
vendor/bin/testlink validate
```

Expected output shows "Non-FQCN @see Tags" section with fixable issues.

### 9.3 Fix Dry-Run

```bash
vendor/bin/testlink validate --fix --dry-run
```

Shows preview of fixes without modifying files.

### 9.4 Fix and Verify

```bash
vendor/bin/testlink validate --fix

# Verify @see now has leading backslash:
# @see \Tests\Unit\UserServiceTest::test_creates_user
```

---

## Phase 10: Edge Cases - Pest

**Tasks (4):**

### 10.1 Describe Blocks

```php
describe('UserService', function () {
    describe('create method', function () {
        it('creates a user with valid data', function () {
            // ...
        })->linksAndCovers('@nested-describe');
    });
});
```

Production:
```php
#[TestedBy('@nested-describe')]
public function create(): void { }
```

```bash
# Test identifiers should be:
# "UserService > create method > creates a user with valid data"
vendor/bin/testlink pair --dry-run
```

### 10.2 it() Syntax

```php
it('does something', function () {
    // ...
})->linksAndCovers(Service::class.'::method');
```

### 10.3 Arrow Function Tests

```php
test('arrow function test', fn() => expect(true)->toBeTrue())
    ->linksAndCovers('@arrow-test');

it('works with arrow', fn() => expect(1)->toBe(1))
    ->linksAndCovers('@arrow-test');
```

```bash
# Arrow function tests should be detected
vendor/bin/testlink pair --dry-run
```

### 10.4 Chained linksAndCovers

```php
test('chained test', function () {
    // ...
})->linksAndCovers(ServiceA::class.'::methodA')
  ->linksAndCovers(ServiceB::class.'::methodB')
  ->linksAndCovers('@placeholder');
```

---

## Phase 11: Edge Cases - PHPUnit

**Tasks (4):**

### 11.1 LinksAndCovers Attribute

```php
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class PHPUnitExampleTest extends TestCase
{
    #[LinksAndCovers('@phpunit-test')]
    public function test_example(): void
    {
        $this->assertTrue(true);
    }
}
```

```bash
vendor/bin/testlink validate
vendor/bin/testlink pair --dry-run
vendor/bin/testlink pair

# Result: #[LinksAndCovers(ExampleClass::class, 'exampleMethod')]
```

### 11.2 DataProvider Tests

```php
use PHPUnit\Framework\Attributes\DataProvider;

#[LinksAndCovers('@calc-add')]
#[DataProvider('additionProvider')]
public function test_addition(int $a, int $b, int $expected): void
{
    // ...
}

public static function additionProvider(): array
{
    return [[1, 2, 3], [0, 0, 0], [-1, 1, 0]];
}
```

### 11.3 Multiple TestedBy on Same Method

```php
#[TestedBy(TestA::class, 'test_one')]
#[TestedBy(TestB::class, 'test_two')]
#[TestedBy('@placeholder')]
public function multiAttributeMethod(): void { }
```

### 11.4 Abstract/Static/Final Methods

```php
abstract class BaseService
{
    #[TestedBy(BaseServiceTest::class, 'test_abstract')]
    abstract public function process(): void;

    #[TestedBy(BaseServiceTest::class, 'test_static')]
    public static function getInstance(): self { }

    #[TestedBy(BaseServiceTest::class, 'test_final')]
    final public function lock(): void { }
}
```

**Forward Sync:** All method types should get @see tags via sync.

```bash
vendor/bin/testlink sync --dry-run
vendor/bin/testlink sync
```

Verify @see tags added to all three methods (abstract, static, final).

**Reverse Sync:** Test `linksAndCovers` to static methods:

```php
// Test file
#[LinksAndCovers(BaseService::class, 'getInstance')]
public function test_static(): void { }
```

```bash
vendor/bin/testlink sync --dry-run
```

Output should show `#[TestedBy]` would be added to `getInstance()` static method.

---

## Phase 12: @see Edge Cases

**Tasks (4):**

### 12.1 Existing Docblock Preservation

Before sync:
```php
/**
 * Create a new user.
 *
 * @param string $name User's name
 * @return User The created user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(string $name): User
```

After sync:
```php
/**
 * Create a new user.
 *
 * @param string $name User's name
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 * @return User The created user
 */
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(string $name): User
```

### 12.2 Duplicate @see Prevention

```bash
# Run sync multiple times
vendor/bin/testlink sync
vendor/bin/testlink sync
vendor/bin/testlink sync

# Still only one @see should exist
```

### 12.3 Different Indentation Levels

Test with 2-space, 4-space, and tab indentation:

4-space:
```php
class Foo {
    /**
     * @see \Tests\FooTest::test
     */
    public function bar() {}
}
```

2-space:
```php
class Foo {
  /**
   * @see \Tests\FooTest::test
   */
  public function bar() {}
}
```

Tab:
```php
class Foo {
	/**
	 * @see \Tests\FooTest::test
	 */
	public function bar() {}
}
```

### 12.4 Constructor and Magic Methods

```php
class Service
{
    #[TestedBy(ServiceTest::class, 'test_constructor')]
    public function __construct() {}

    #[TestedBy(ServiceTest::class, 'test_invoke')]
    public function __invoke(): void {}

    #[TestedBy(ServiceTest::class, 'test_to_string')]
    public function __toString(): string {}
}
```

---

## Phase 13: Error Handling

**Tasks (4):**

### 13.1 Orphan Production Placeholder

Only production has placeholder (no matching test):

```php
#[TestedBy('@orphan-prod')]
public function orphanMethod(): void { }
```

```bash
# Should show error: "Placeholder @orphan-prod has no matching test entries"
vendor/bin/testlink pair --dry-run
```

### 13.2 Orphan Test Placeholder

Only test has placeholder (no matching production):

```php
test('orphan test', fn() => ...)->linksAndCovers('@orphan-test');
```

```bash
# Should show error: "Placeholder @orphan-test has no matching production entries"
vendor/bin/testlink pair --dry-run
```

### 13.3 Invalid Placeholder Formats

```bash
# These should show errors
vendor/bin/testlink pair --placeholder=invalid
vendor/bin/testlink pair --placeholder=@123
vendor/bin/testlink pair --placeholder=@

# These should work (if placeholders exist)
vendor/bin/testlink pair --placeholder=@A
vendor/bin/testlink pair --placeholder=@user-create
vendor/bin/testlink pair --placeholder=@UserCreate123
vendor/bin/testlink pair --placeholder=@test_helper

# @@prefix also valid (for @see tags - PHPUnit only)
vendor/bin/testlink pair --placeholder=@@A
vendor/bin/testlink pair --placeholder=@@user-create
```

### 13.4 Invalid Path

```bash
vendor/bin/testlink report --path=/nonexistent
```

---

## Phase 14: Path & Framework Filters

**Tasks (4):**

### 14.1 Path Filter

```bash
# Scan specific directories
vendor/bin/testlink report --path=src/Services
vendor/bin/testlink report --path=app/Models

# Test directory
vendor/bin/testlink validate --path=tests/Unit
vendor/bin/testlink validate --path=tests/Feature

# With sync
vendor/bin/testlink sync --path=tests/Unit --dry-run
```

### 14.2 Framework Filter

```bash
# Only Pest tests
vendor/bin/testlink report --framework=pest
vendor/bin/testlink sync --framework=pest --dry-run

# Only PHPUnit tests
vendor/bin/testlink report --framework=phpunit
vendor/bin/testlink sync --framework=phpunit --dry-run

# Auto (default)
vendor/bin/testlink sync --framework=auto --dry-run
```

### 14.3 Combined Filters

```bash
# Specific directory and framework
vendor/bin/testlink sync --path=tests/Unit --framework=phpunit --dry-run
vendor/bin/testlink report --path=src/Services --framework=pest
```

### 14.4 Command Help

```bash
vendor/bin/testlink report --help
vendor/bin/testlink validate --help
vendor/bin/testlink sync --help
vendor/bin/testlink pair --help
```

---

## Phase 15: @@Prefix for @see Tags

**Tasks (5):**

The `@@` prefix generates `@see` tags instead of attributes. This is PHPUnit only.

### 15.1 Setup @@Prefix Placeholder (PHPUnit)

Production:
```php
// src/Services/SeeTagService.php
use TestFlowLabs\TestingAttributes\TestedBy;

class SeeTagService
{
    #[TestedBy('@@see-tag-test')]
    public function process(): void
    {
        // ...
    }
}
```

Test (PHPUnit):
```php
// tests/Unit/SeeTagServiceTest.php
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class SeeTagServiceTest extends TestCase
{
    #[LinksAndCovers('@@see-tag-test')]
    public function testProcess(): void
    {
        $this->assertTrue(true);
    }
}
```

### 15.2 Pair Dry-Run (@@prefix)

```bash
# Should show @see tag generation
vendor/bin/testlink pair --dry-run

# Output should indicate @see tags will be generated:
# "@@see-tag-test  1 production × 1 test = 1 link"
```

### 15.3 Pair and Verify @see Tags

```bash
vendor/bin/testlink pair
```

**Verify production file:**
```php
// src/Services/SeeTagService.php
/** @see \Tests\Unit\SeeTagServiceTest::testProcess */
public function process(): void
```

**Verify test file:**
```php
// tests/Unit/SeeTagServiceTest.php
/** @see \App\Services\SeeTagService::process */
public function testProcess(): void
```

Note:
- `#[TestedBy]` replaced with `/** @see ... */`
- `#[LinksAndCovers]` replaced with `/** @see ... */`
- FQCN format with leading backslash

### 15.4 @@Prefix with Pest (Error)

Production:
```php
#[TestedBy('@@pest-error-test')]
public function method(): void { }
```

Test (Pest):
```php
test('pest test', function () {
    // ...
})->linksAndCovers('@@pest-error-test');
```

```bash
# Should show error about Pest not supporting @see tags
vendor/bin/testlink pair --dry-run

# Expected error:
# "Placeholder @@pest-error-test uses @@prefix (for @see tags) but Pest tests
#  do not support @see tags. Use @pest-error-test instead."
```

### 15.5 N:M @@Prefix Resolution

Production (2 methods):
```php
#[TestedBy('@@nm-see')]
public function methodA(): void { }

#[TestedBy('@@nm-see')]
public function methodB(): void { }
```

Test (2 tests, PHPUnit):
```php
#[LinksAndCovers('@@nm-see')]
public function testMethodA(): void { }

#[LinksAndCovers('@@nm-see')]
public function testMethodB(): void { }
```

```bash
vendor/bin/testlink pair --dry-run

# Should show "2 production x 2 tests = 4 links"
```

After pair:
- `methodA()` has 2 @see tags
- `methodB()` has 2 @see tags
- `testMethodA()` has 2 @see tags
- `testMethodB()` has 2 @see tags

---

## Checklist

After each phase, verify:

- [ ] Commands run without errors
- [ ] Output is in expected format
- [ ] Exit code is correct (0 = success, 1 = error)
- [ ] File changes are correct
- [ ] JSON output is valid and parseable
- [ ] `--dry-run` does not modify files
- [ ] `--verbose` shows more information

### Sync Specific Checks

- [ ] Forward sync: `#[TestedBy]` → `linksAndCovers()`/`#[LinksAndCovers]`
- [ ] Reverse sync: `linksAndCovers()`/`#[LinksAndCovers]` → `#[TestedBy]`
- [ ] CLI shows both forward and reverse actions
- [ ] Static methods work with reverse sync
- [ ] Abstract methods work with forward sync (@see tags)
- [ ] Final methods work with sync
- [ ] Multiple `#[TestedBy]` on same method supported
- [ ] `--link-only` uses `links()` instead of `linksAndCovers()`
- [ ] `--prune` requires `--force`
- [ ] Orphan links removed with `--prune --force`

### @see Tag Specific Checks

- [ ] @see tag created after sync
- [ ] @see tag format is correct: `@see \FQCN::method`
- [ ] @see tag has correct indentation
- [ ] Existing docblock preserved, @see added
- [ ] No duplicate @see tags
- [ ] Report shows @see tags
- [ ] Validate shows @see count
- [ ] Orphan @see detected
- [ ] Prune removes orphan @see
- [ ] Empty docblock removed after prune
- [ ] Other PHPDoc tags preserved

### FQCN Validation Specific Checks

- [ ] Non-FQCN @see tags detected
- [ ] Fixable vs unfixable issues distinguished
- [ ] `--fix --dry-run` shows preview
- [ ] `--fix` converts to FQCN format
- [ ] Use statement resolution works (simple, aliased, grouped)

### @@Prefix Specific Checks

- [ ] `@@` prefix recognized as valid placeholder
- [ ] `@@` prefix generates @see tags (not attributes)
- [ ] @see tags use FQCN format with leading backslash
- [ ] `@@` prefix with PHPUnit works correctly
- [ ] `@@` prefix with Pest shows clear error
- [ ] N:M matching works with `@@` prefix
- [ ] Mixed `@` and `@@` placeholders handled separately
- [ ] `--placeholder=@@name` filter works

---

## Quick Test Script

```bash
#!/bin/bash
set -e

echo "=== TestLink Manual Test ==="

echo -e "\n1. Version"
vendor/bin/testlink --version

echo -e "\n2. Help"
vendor/bin/testlink --help

echo -e "\n3. Report"
vendor/bin/testlink report

echo -e "\n4. Report JSON"
vendor/bin/testlink report --json

echo -e "\n5. Validate"
vendor/bin/testlink validate

echo -e "\n6. Validate JSON"
vendor/bin/testlink validate --json

echo -e "\n7. Sync Dry-Run"
vendor/bin/testlink sync --dry-run

echo -e "\n8. Pair Dry-Run"
vendor/bin/testlink pair --dry-run

echo -e "\n=== All basic tests passed ==="
```

### @see Tag Test Script

```bash
#!/bin/bash
set -e

echo "=== @see Tag Tests ==="

echo -e "\n1. Sync (generates @see tags)"
vendor/bin/testlink sync

echo -e "\n2. Report (@see tags should appear)"
vendor/bin/testlink report

echo -e "\n3. Validate (@see count should appear)"
vendor/bin/testlink validate

echo -e "\n4. Validate FQCN"
vendor/bin/testlink validate --fix --dry-run

echo -e "\n=== @see Tag tests completed ==="
```

### Bidirectional Sync Test Script

```bash
#!/bin/bash
set -e

echo "=== Bidirectional Sync Tests ==="

echo -e "\n1. Sync Dry-Run (shows both forward and reverse)"
vendor/bin/testlink sync --dry-run

echo -e "\n2. Sync (apply changes)"
vendor/bin/testlink sync

echo -e "\n3. Report (shows all links)"
vendor/bin/testlink report

echo -e "\n4. Validate (verify consistency)"
vendor/bin/testlink validate

echo -e "\n=== Bidirectional Sync tests completed ==="
```
