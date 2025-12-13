# Getting Started

By the end of this tutorial, you'll be able to **Cmd+Click** from production code to its tests, and from tests back to production code.

No more searching for which tests cover a method. No more guessing what a test is supposed to verify. Just click.

## Prerequisites

- PHP 8.1 or higher
- Composer
- A PHP project with Pest or PHPUnit

## Step 1: Install the Packages

TestLink uses a two-package architecture. You need to install both:

```sh
# Production dependency - attributes for production code
composer require testflowlabs/test-attributes

# Dev dependency - CLI tools
composer require --dev testflowlabs/testlink
```

::: tip Why Two Packages?
The `test-attributes` package contains PHP attributes like `#[TestedBy]` that you place on production code. Since PHP needs these classes to load your production code, it must be a production dependency.

The `testlink` package contains CLI tools that only run during development. See [Two-Package Architecture](/explanation/two-package-architecture) for details.
:::

## Step 2: Verify Installation

Run the TestLink CLI to verify the installation:

```bash
./vendor/bin/testlink
```

You should see:

```sh
  TestLink dev-master

  Detected frameworks: pest (phpunit compatible)


  USAGE
    testlink <command> [options]

  COMMANDS
    • report      Show coverage links report
    • validate    Validate coverage link synchronization
    • sync        Sync coverage links across test files
    • pair        Resolve placeholder markers into real links

  GLOBAL OPTIONS
    • --help, -h        Show help information
    • --version, -v     Show version
    • --verbose         Show detailed output
    • --no-color        Disable colored output

  Run "testlink <command> --help" for command-specific help.
```

## Step 3: Add Your First Link

Let's add a simple link to see TestLink in action.

### Production Code

Open a production class and add the `#[TestedBy]` attribute:

:::tabs key:stack
== Pest

```php
<?php
// src/Calculator.php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class Calculator
{
    #[TestedBy('Tests\CalculatorTest', 'adds two numbers')]
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}
```

== PHPUnit + Attributes

```php
<?php
// src/Calculator.php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class Calculator
{
    #[TestedBy('Tests\CalculatorTest', 'test_adds_two_numbers')]
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}
```

== PHPUnit + @see

```php
<?php
// src/Calculator.php

namespace App;

class Calculator
{
    /**
     * @see \Tests\CalculatorTest::test_adds_two_numbers
     */
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}
```

:::

### Test Code

Now create the corresponding test:

:::tabs key:stack
== Pest

```php
<?php
// tests/CalculatorTest.php

use App\Calculator;

test('adds two numbers', function () {
    $calculator = new Calculator();

    expect($calculator->add(2, 3))->toBe(5);
})->linksAndCovers(Calculator::class.'::add');
```

== PHPUnit + Attributes

```php
<?php
// tests/CalculatorTest.php

namespace Tests;

use App\Calculator;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class CalculatorTest extends TestCase
{
    #[LinksAndCovers(Calculator::class, 'add')]
    public function test_adds_two_numbers(): void
    {
        $calculator = new Calculator();

        $this->assertSame(5, $calculator->add(2, 3));
    }
}
```

== PHPUnit + @see

```php
<?php
// tests/CalculatorTest.php

namespace Tests;

use App\Calculator;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    /**
     * @see \App\Calculator::add
     */
    public function test_adds_two_numbers(): void
    {
        $calculator = new Calculator();

        $this->assertSame(5, $calculator->add(2, 3));
    }
}
```

:::

## Step 4: Run the Report

Now run the report command to see your link:

```bash
./vendor/bin/testlink report
```

You should see output like:

```
  Coverage Links Report
  ─────────────────────

  App\Calculator
    add()
    → Tests\CalculatorTest::adds two numbers

  Summary
    Methods with tests: 1
    Total test links: 1
```

You've created your first bidirectional link. Now try this in your IDE:

1. Open `src/Calculator.php`
2. **Cmd+Click** (or Ctrl+Click) on the `@see` tag
3. Your IDE jumps directly to the test

The same works in reverse—from the test's `@see` tag back to production code. This is the core value of TestLink: instant navigation.

## Step 5: Validate Your Links

Run the validate command to ensure everything is in sync:

```bash
./vendor/bin/testlink validate
```

You should see:

```
  Validation Report
  ─────────────────

  Link Summary
    PHPUnit attribute links: 1
    Pest method chain links: 0
    @see tags: 0
    Total links: 1

  ✓ All links are valid!
```

## What's Next?

You now have navigable code—Cmd+Click works between tests and production. To get the most out of TestLink:

- [Your First Bidirectional Link](./first-bidirectional-link) - More linking patterns
- [Understanding Reports](./understanding-reports) - See all relationships at a glance
- [Keep Links Valid](/how-to/run-validation-in-ci) - Ensure links stay accurate

## Quick Reference

| Command | Description |
|---------|-------------|
| `testlink report` | Show all coverage links |
| `testlink validate` | Check if links are synchronized |
| `testlink sync` | Auto-generate missing links |
| `testlink pair` | Resolve placeholder markers |

For detailed command reference, see [CLI Commands](/reference/cli/).
