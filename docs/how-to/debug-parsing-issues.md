# Debug Parsing Issues

This guide helps you troubleshoot common parsing problems with TestLink.

## Common Issues

### 1. Test Not Found

**Error:**
```
Test 'test_creates_user' not found in Tests\UserServiceTest
```

**Causes:**
- Test name mismatch
- Test in describe block (name is combined)
- Test file not autoloaded

**Solutions:**

Check the actual test name:

```php
// If using describe blocks
describe('UserService', function () {
    test('creates user', function () { });
});
// Full name is: "UserService creates user"

// In TestedBy, use full name:
#[TestedBy('Tests\UserServiceTest', 'UserService creates user')]
```

Verify the test exists:

```bash
./vendor/bin/pest --filter="creates user"
```

### 2. Class Not Found

**Error:**
```
Class 'App\Services\UserService' not found
```

**Solutions:**

Run autoload dump:
```bash
composer dump-autoload
```

Check namespace matches file path:
```php
// File: src/Services/UserService.php
namespace App\Services;  // Must match PSR-4 mapping
```

Verify composer.json autoload:
```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

### 3. Method Not Found

**Error:**
```
Method 'create' not found in App\Services\UserService
```

**Causes:**
- Typo in method name
- Method is private
- Method in parent class

**Solutions:**

Check the exact method name:
```php
// Wrong
#[TestedBy('Tests\Test', 'test')]
public function Create()  // Capital C

// Correct
#[TestedBy('Tests\Test', 'test')]
public function create()  // lowercase c
```

### 4. Pest File Not Parsed

**Error:**
```
No tests found in tests/UserServiceTest.php
```

**Causes:**
- File doesn't match Pest pattern
- Missing test() or it() calls
- Syntax error in file

**Solutions:**

Verify file uses Pest syntax:
```php
<?php
// Must have test() or it() calls
test('example', function () {
    expect(true)->toBeTrue();
});
```

Check for syntax errors:
```bash
php -l tests/UserServiceTest.php
```

### 5. PHPUnit File Not Parsed

**Error:**
```
No tests found in tests/UserServiceTest.php
```

**Causes:**
- Class doesn't extend TestCase
- Methods don't start with 'test'
- No #[Test] attribute

**Solutions:**

Verify PHPUnit structure:
```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function test_creates_user(): void  // Must start with 'test'
    {
        // ...
    }
}
```

## Debugging Commands

### Verbose output

```bash
./vendor/bin/testlink report --verbose
./vendor/bin/testlink validate --verbose
```

### JSON output for parsing

```bash
./vendor/bin/testlink report --json > report.json
./vendor/bin/testlink validate --json > validate.json
```

### Check specific path

```bash
./vendor/bin/testlink report --path=tests/Unit/UserServiceTest.php
```

## Checking What's Scanned

### List scanned files

```bash
./vendor/bin/testlink report --verbose 2>&1 | grep "Scanning"
```

### Verify test discovery

```bash
# For Pest
./vendor/bin/pest --list-tests

# For PHPUnit
./vendor/bin/phpunit --list-tests
```

## Common Syntax Errors

### Missing semicolon

```php
// Wrong - breaks parsing
test('creates user', function () {
    // ...
})  // Missing semicolon after chain

->linksAndCovers(UserService::class.'::create')

// Correct
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

### Wrong string concatenation

```php
// Wrong - space before ::
->linksAndCovers(UserService::class. '::create')

// Correct - no space
->linksAndCovers(UserService::class.'::create')
```

### Invalid class reference

```php
// Wrong - using string instead of ::class
#[TestedBy('App\UserService', 'test')]

// Correct - using ::class constant (recommended)
#[TestedBy(UserService::class, 'test')]

// Also correct - full string path
#[TestedBy('App\Services\UserService', 'test')]
```

## Framework Detection Issues

### Check detected framework

```bash
./vendor/bin/testlink report --verbose 2>&1 | grep "Framework"
```

### Force framework

If auto-detection fails, check your composer.json has the correct dependencies:

For Pest:
```json
{
    "require-dev": {
        "pestphp/pest": "^2.0"
    }
}
```

For PHPUnit:
```json
{
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

## Getting Help

### Gather diagnostic info

```bash
# PHP version
php -v

# Composer packages
composer show testflowlabs/testlink
composer show testflowlabs/test-attributes

# Autoload status
composer dump-autoload -v
```

### Report issues

If you can't resolve the issue:

1. Create minimal reproduction
2. Include error message
3. Include relevant code snippets
4. Open issue at: https://github.com/testflowlabs/testlink/issues
