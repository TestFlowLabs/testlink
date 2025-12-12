# Troubleshooting

Common issues and solutions when using TestLink.

## Installation Issues

### "Class not found" in Production

**Problem:** Your application fails to load classes with `#[TestedBy]` attributes in production.

```
PHP Fatal error: Class 'TestFlowLabs\TestingAttributes\TestedBy' not found
```

**Solution:** Install `test-attributes` as a production dependency:

```bash
composer require testflowlabs/test-attributes
```

The `test-attributes` package must be a production dependency because PHP needs the attribute classes when autoloading your code. See the [Installation Guide](/introduction/installation) for details.

### Pest Methods Not Available

**Problem:** `->linksAndCovers()` or `->links()` methods are not recognized in Pest tests.

```
Call to undefined method linksAndCovers()
```

**Solution:** Initialize the RuntimeBootstrap in your `tests/Pest.php`:

```php
// tests/Pest.php
use TestFlowLabs\TestLink\Runtime\RuntimeBootstrap;

RuntimeBootstrap::init();
```

## Validation Issues

### Links Not Detected

**Problem:** `testlink validate` shows 0 links even though you have attributes in your code.

**Possible causes:**

1. **Wrong namespace** - Ensure you're using the correct attribute class:
   ```php
   use TestFlowLabs\TestingAttributes\LinksAndCovers; // Correct
   ```

2. **Missing RuntimeBootstrap** - For Pest tests, ensure you've initialized the bootstrap.

3. **Path filter** - If using `--path`, ensure it includes your test files:
   ```bash
   testlink validate --path=tests/Unit
   ```

### New Files Not Detected

**Problem:** `testlink validate` or `testlink pair` doesn't detect newly created files.

**Solution:** After creating new test or production files, run:

```bash
composer dump-autoload -o
```

This updates Composer's classmap so TestLink can find new classes. This is especially important after creating files with `#[TestedBy]` or `#[LinksAndCovers]` attributes.

### Unresolved Placeholders Warning

**Problem:** Validation shows warnings about unresolved placeholders:

```
⚠ @user-create  (1 production, 2 tests)
```

**Solution:** Run `testlink pair` to resolve placeholders:

```bash
# Preview changes
testlink pair --dry-run

# Apply changes
testlink pair
```

Or use `--strict` to fail on unresolved placeholders (useful in CI):

```bash
testlink validate --strict
```

### Duplicate Links Found

**Problem:** Validation reports duplicate links:

```
! Tests\Unit\UserServiceTest::test_creates_user
  → App\Services\UserService::create
```

**Cause:** The same test has both `#[LinksAndCovers]` attribute AND `->linksAndCovers()` method call.

**Solution:** Use only one linking method per test. Choose either:
- PHPUnit attributes: `#[LinksAndCovers(Class::class, 'method')]`
- Pest method chaining: `->linksAndCovers(Class::class.'::method')`

## Sync Issues

### Test File Not Found

**Problem:** Sync reports "File not found" errors:

```
✗ File not found: tests/Unit/MissingTest.php
```

**Cause:** The `#[TestedBy]` attribute references a test class that doesn't exist.

**Solution:** Either:
1. Create the missing test file
2. Fix the test class name in the `#[TestedBy]` attribute
3. Remove the `#[TestedBy]` attribute

### Test Case Not Found

**Problem:** Sync reports "Test case not found" errors:

```
✗ Test case not found: "missing test" in tests/Unit/UserServiceTest.php
```

**Cause:** The test name in `#[TestedBy]` doesn't match any test in the file.

**Solution:** Verify the test name matches exactly. For Pest tests in describe blocks, use the full path:

```php
// Test definition
describe('UserService', function () {
    test('creates user', function () { });
});

// TestedBy attribute (note the full path)
#[TestedBy(UserServiceTest::class, 'UserService > creates user')]
```

### Parse Errors

**Problem:** Sync reports "Could not parse" errors:

```
✗ Could not parse: tests/Unit/BrokenTest.php
```

**Cause:** The test file has PHP syntax errors.

**Solution:** Fix the syntax errors in the test file. Run `php -l tests/Unit/BrokenTest.php` to identify issues.

## Placeholder Pairing Issues

### No Matching Production Entries

**Problem:** `testlink pair` reports orphan test placeholders:

```
✗ Placeholder @missing has no matching production entries
```

**Cause:** Tests use a placeholder that doesn't exist in any production code.

**Solution:** Add the placeholder to the production method:

```php
#[TestedBy('@missing')]
public function someMethod(): void { }
```

### No Matching Test Entries

**Problem:** `testlink pair` reports orphan production placeholders:

```
✗ Placeholder @orphan has no matching test entries
```

**Cause:** Production code uses a placeholder that doesn't exist in any test.

**Solution:** Add the placeholder to the test:

::: code-group

```php [Pest]
test('some test', function () { })->linksAndCovers('@orphan');
```

```php [PHPUnit]
#[LinksAndCovers('@orphan')]
public function test_something(): void { }
```

:::

### Invalid Placeholder Format

**Problem:** Placeholder validation fails:

```
✗ Invalid placeholder format: invalid
```

**Cause:** Placeholder doesn't follow the required format.

**Valid formats:**
- `@A`, `@B`, `@C` - Single letters
- `@user-create` - Kebab-case
- `@UserCreate` - PascalCase
- `@user_create` - Snake_case with underscores

**Invalid formats:**
- `@` - Missing identifier
- `@123` - Cannot start with number
- `@invalid!` - No special characters

## Framework Detection Issues

### Wrong Framework Detected

**Problem:** TestLink detects the wrong framework or shows unexpected output.

**Solution:** Use the `--framework` option to specify the framework:

```bash
testlink validate --framework=pest
testlink validate --framework=phpunit
```

### Both Frameworks Detected

**Problem:** Output shows "pest (phpunit compatible)" but you only want one.

**Explanation:** This is normal. Pest runs on top of PHPUnit, so both are technically available. TestLink handles this automatically.

## IDE Integration Issues

### PhpStorm Doesn't Recognize Attributes

**Solution:**
1. Invalidate caches: File → Invalidate Caches → Invalidate and Restart
2. Ensure vendor directory is indexed
3. Check that `testflowlabs/test-attributes` is installed

### VS Code Doesn't Show Attribute Hints

**Solution:**
1. Install PHP Intelephense or PHP Tools extension
2. Run `composer dump-autoload`
3. Restart VS Code

## CI/CD Issues

### Validation Fails in CI but Works Locally

**Possible causes:**

1. **Missing dependencies** - Ensure both packages are installed:
   ```bash
   composer require testflowlabs/test-attributes
   composer require --dev testflowlabs/testlink
   ```

2. **Different Composer install** - Use `composer install` not `composer update` in CI

3. **Path differences** - Ensure `--path` options use correct paths for CI environment

### Exit Code Issues

TestLink uses these exit codes:

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Validation failed, errors occurred, or (with `--strict`) warnings found |

In CI, check for exit code 0:

```yaml
- name: Validate Coverage Links
  run: ./vendor/bin/testlink validate
  # Will fail the job if exit code is non-zero
```

## Getting Help

If you encounter issues not covered here:

1. Check the [GitHub Issues](https://github.com/testflowlabs/testlink/issues)
2. Run commands with `--verbose` for detailed output
3. Open a new issue with:
   - TestLink version (`testlink --version`)
   - PHP version (`php --version`)
   - Framework versions (Pest/PHPUnit)
   - Steps to reproduce
   - Expected vs actual behavior
