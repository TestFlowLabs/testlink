# Your First Bidirectional Link

In this tutorial, you'll learn how to create bidirectional links between production code and tests. These links create explicit traceability in both directions.

## What is Bidirectional Linking?

Bidirectional linking means:
1. **Production → Test**: Your production code declares which tests verify it (`#[TestedBy]`)
2. **Test → Production**: Your tests declare which production methods they cover (`linksAndCovers()` or `#[LinksAndCovers]`)

Both directions should match. TestLink validates this synchronization.

## Prerequisites

Make sure you've completed the [Getting Started](./getting-started) tutorial first.

## Step 1: Create a Production Class

Let's create a simple `UserValidator` class:

```php
<?php
// src/UserValidator.php

namespace App;

class UserValidator
{
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function isValidAge(int $age): bool
    {
        return $age >= 18 && $age <= 120;
    }
}
```

## Step 2: Write Tests First (Without Links)

Let's write tests for our validator:

:::tabs key:stack
== Pest

```php
<?php
// tests/UserValidatorTest.php

use App\UserValidator;

describe('UserValidator', function () {
    describe('isValidEmail', function () {
        test('returns true for valid email', function () {
            $validator = new UserValidator();

            expect($validator->isValidEmail('user@example.com'))->toBeTrue();
        });

        test('returns false for invalid email', function () {
            $validator = new UserValidator();

            expect($validator->isValidEmail('invalid'))->toBeFalse();
        });
    });

    describe('isValidAge', function () {
        test('returns true for valid age', function () {
            $validator = new UserValidator();

            expect($validator->isValidAge(25))->toBeTrue();
        });

        test('returns false for age under 18', function () {
            $validator = new UserValidator();

            expect($validator->isValidAge(17))->toBeFalse();
        });
    });
});
```

== PHPUnit + Attributes

```php
<?php
// tests/UserValidatorTest.php

namespace Tests;

use App\UserValidator;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    public function test_returns_true_for_valid_email(): void
    {
        $validator = new UserValidator();

        $this->assertTrue($validator->isValidEmail('user@example.com'));
    }

    public function test_returns_false_for_invalid_email(): void
    {
        $validator = new UserValidator();

        $this->assertFalse($validator->isValidEmail('invalid'));
    }

    public function test_returns_true_for_valid_age(): void
    {
        $validator = new UserValidator();

        $this->assertTrue($validator->isValidAge(25));
    }

    public function test_returns_false_for_age_under_18(): void
    {
        $validator = new UserValidator();

        $this->assertFalse($validator->isValidAge(17));
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/UserValidatorTest.php

namespace Tests;

use App\UserValidator;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    public function test_returns_true_for_valid_email(): void
    {
        $validator = new UserValidator();

        $this->assertTrue($validator->isValidEmail('user@example.com'));
    }

    public function test_returns_false_for_invalid_email(): void
    {
        $validator = new UserValidator();

        $this->assertFalse($validator->isValidEmail('invalid'));
    }

    public function test_returns_true_for_valid_age(): void
    {
        $validator = new UserValidator();

        $this->assertTrue($validator->isValidAge(25));
    }

    public function test_returns_false_for_age_under_18(): void
    {
        $validator = new UserValidator();

        $this->assertFalse($validator->isValidAge(17));
    }
}
```

:::

## Step 3: Add Production-Side Links

Now add links to your production code:

:::tabs key:stack
== Pest

```php
<?php
// src/UserValidator.php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class UserValidator
{
    #[TestedBy('Tests\UserValidatorTest', 'isValidEmail returns true for valid email')]
    #[TestedBy('Tests\UserValidatorTest', 'isValidEmail returns false for invalid email')]
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    #[TestedBy('Tests\UserValidatorTest', 'isValidAge returns true for valid age')]
    #[TestedBy('Tests\UserValidatorTest', 'isValidAge returns false for age under 18')]
    public function isValidAge(int $age): bool
    {
        return $age >= 18 && $age <= 120;
    }
}
```

::: tip Test Names in Pest
For Pest tests with describe blocks, the test name is the combination of describe and test names. For `describe('isValidEmail')` containing `test('returns true for valid email')`, the test name is `isValidEmail returns true for valid email`.
:::

== PHPUnit + Attributes

```php
<?php
// src/UserValidator.php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class UserValidator
{
    #[TestedBy('Tests\UserValidatorTest', 'test_returns_true_for_valid_email')]
    #[TestedBy('Tests\UserValidatorTest', 'test_returns_false_for_invalid_email')]
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    #[TestedBy('Tests\UserValidatorTest', 'test_returns_true_for_valid_age')]
    #[TestedBy('Tests\UserValidatorTest', 'test_returns_false_for_age_under_18')]
    public function isValidAge(int $age): bool
    {
        return $age >= 18 && $age <= 120;
    }
}
```

== PHPUnit + @see

```php
<?php
// src/UserValidator.php

namespace App;

class UserValidator
{
    /**
     * @see \Tests\UserValidatorTest::test_returns_true_for_valid_email
     * @see \Tests\UserValidatorTest::test_returns_false_for_invalid_email
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @see \Tests\UserValidatorTest::test_returns_true_for_valid_age
     * @see \Tests\UserValidatorTest::test_returns_false_for_age_under_18
     */
    public function isValidAge(int $age): bool
    {
        return $age >= 18 && $age <= 120;
    }
}
```

:::

## Step 4: Add Test-Side Links

Now add the reverse links in your tests:

:::tabs key:stack
== Pest

```php
<?php
// tests/UserValidatorTest.php

use App\UserValidator;

describe('UserValidator', function () {
    describe('isValidEmail', function () {
        test('returns true for valid email', function () {
            $validator = new UserValidator();

            expect($validator->isValidEmail('user@example.com'))->toBeTrue();
        })->linksAndCovers(UserValidator::class.'::isValidEmail');

        test('returns false for invalid email', function () {
            $validator = new UserValidator();

            expect($validator->isValidEmail('invalid'))->toBeFalse();
        })->linksAndCovers(UserValidator::class.'::isValidEmail');
    });

    describe('isValidAge', function () {
        test('returns true for valid age', function () {
            $validator = new UserValidator();

            expect($validator->isValidAge(25))->toBeTrue();
        })->linksAndCovers(UserValidator::class.'::isValidAge');

        test('returns false for age under 18', function () {
            $validator = new UserValidator();

            expect($validator->isValidAge(17))->toBeFalse();
        })->linksAndCovers(UserValidator::class.'::isValidAge');
    });
});
```

== PHPUnit + Attributes

```php
<?php
// tests/UserValidatorTest.php

namespace Tests;

use App\UserValidator;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserValidatorTest extends TestCase
{
    #[LinksAndCovers(UserValidator::class, 'isValidEmail')]
    public function test_returns_true_for_valid_email(): void
    {
        $validator = new UserValidator();

        $this->assertTrue($validator->isValidEmail('user@example.com'));
    }

    #[LinksAndCovers(UserValidator::class, 'isValidEmail')]
    public function test_returns_false_for_invalid_email(): void
    {
        $validator = new UserValidator();

        $this->assertFalse($validator->isValidEmail('invalid'));
    }

    #[LinksAndCovers(UserValidator::class, 'isValidAge')]
    public function test_returns_true_for_valid_age(): void
    {
        $validator = new UserValidator();

        $this->assertTrue($validator->isValidAge(25));
    }

    #[LinksAndCovers(UserValidator::class, 'isValidAge')]
    public function test_returns_false_for_age_under_18(): void
    {
        $validator = new UserValidator();

        $this->assertFalse($validator->isValidAge(17));
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/UserValidatorTest.php

namespace Tests;

use App\UserValidator;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    /**
     * @see \App\UserValidator::isValidEmail
     */
    public function test_returns_true_for_valid_email(): void
    {
        $validator = new UserValidator();

        $this->assertTrue($validator->isValidEmail('user@example.com'));
    }

    /**
     * @see \App\UserValidator::isValidEmail
     */
    public function test_returns_false_for_invalid_email(): void
    {
        $validator = new UserValidator();

        $this->assertFalse($validator->isValidEmail('invalid'));
    }

    /**
     * @see \App\UserValidator::isValidAge
     */
    public function test_returns_true_for_valid_age(): void
    {
        $validator = new UserValidator();

        $this->assertTrue($validator->isValidAge(25));
    }

    /**
     * @see \App\UserValidator::isValidAge
     */
    public function test_returns_false_for_age_under_18(): void
    {
        $validator = new UserValidator();

        $this->assertFalse($validator->isValidAge(17));
    }
}
```

:::

## Step 5: Validate the Links

Run validation to ensure both directions match:

```bash
./vendor/bin/testlink validate
```

You should see:

```
  Validation Report
  ─────────────────

  Link Summary
    PHPUnit attribute links: 4
    Pest method chain links: 0
    @see tags: 0
    Total links: 4

  All links are valid!
```

## Step 6: View the Report

See the complete picture with the report command:

```bash
./vendor/bin/testlink report
```

```
  Coverage Links Report
  ─────────────────────

  App\UserValidator
    isValidEmail()
    → Tests\UserValidatorTest::test_returns_true_for_valid_email
    → Tests\UserValidatorTest::test_returns_false_for_invalid_email

    isValidAge()
    → Tests\UserValidatorTest::test_returns_true_for_valid_age
    → Tests\UserValidatorTest::test_returns_false_for_age_under_18

  Summary
    Methods with tests: 2
    Total test links: 4
```

## What Happens When Links Don't Match?

If you add a `#[TestedBy]` without a corresponding test link (`->linksAndCovers()` in Pest, `#[LinksAndCovers]` in PHPUnit, or `@see` tag), validation will fail:

```bash
./vendor/bin/testlink validate
```

```
  Validation Report
  ─────────────────

  Link Summary
    PHPUnit attribute links: 5
    Pest method chain links: 0
    @see tags: 0
    Total links: 4

  ✗ Found 1 orphan TestedBy link(s):
    App\UserValidator::isValidEmail
      → Tests\UserValidatorTest::test_new_test (test not found)
```

This ensures your documentation stays accurate.

## What's Next?

Now that you understand bidirectional linking:

- [Understanding Reports](./understanding-reports) - Learn to interpret report output
- [TDD Workflow](./tdd/) - Learn to add links during TDD
- [Placeholder Strategy](/explanation/placeholder-strategy) - Temporary markers for rapid development
