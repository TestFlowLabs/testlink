# Getting Started

This tutorial will guide you through installing TestLink and running your first report. By the end, you'll have TestLink set up and ready to use in your project.

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

Congratulations! You've created your first bidirectional link between production code and tests.

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

Now that you have TestLink set up, continue with these tutorials:

- [Your First Bidirectional Link](./first-bidirectional-link) - Learn more about linking patterns
- [Understanding Reports](./understanding-reports) - Learn to read report output
- [TDD Workflow](./tdd/) - Integrate TestLink into your TDD workflow

## Quick Reference

| Command | Description |
|---------|-------------|
| `testlink report` | Show all coverage links |
| `testlink validate` | Check if links are synchronized |
| `testlink sync` | Auto-generate missing links |
| `testlink pair` | Resolve placeholder markers |

For detailed command reference, see [CLI Commands](/reference/cli/).
