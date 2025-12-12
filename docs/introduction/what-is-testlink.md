# What is TestLink?

TestLink is a PHP library that creates **coverage links** between your tests and production code. It works with both **Pest** and **PHPUnit**, helping you track which tests cover which methods and ensuring your test suite stays aligned with your codebase.

## The Problem

As codebases grow, maintaining the relationship between tests and production code becomes challenging:

- **Which tests cover this method?** When modifying production code, you need to know which tests to run.
- **Is this test still relevant?** When a method is refactored or removed, how do you find the associated tests?
- **Are all methods tested?** How do you ensure critical methods have corresponding tests?

## The Solution

TestLink provides a simple mechanism for linking tests to production code:

::: code-group

```php [Pest]
test('processes valid payments', function () {
    $result = app(PaymentService::class)->process($validPayment);

    expect($result->success)->toBeTrue();
})->linksAndCovers(PaymentService::class.'::process');
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class PaymentServiceTest extends TestCase
{
    #[LinksAndCovers(PaymentService::class, 'process')]
    public function test_processes_valid_payments(): void
    {
        $result = app(PaymentService::class)->process($this->validPayment);

        $this->assertTrue($result->success);
    }
}
```

:::

## Key Features

### Coverage Reports

Generate reports showing test coverage relationships:

```bash
testlink report          # Console output
testlink report --json   # JSON output for CI/CD
```

### Validation

Verify that all links are valid:

```bash
testlink validate
```

### Framework Agnostic

Works with Pest, PHPUnit, or both in the same project. Use your preferred testing framework.

## Benefits

Having explicit coverage links provides:

1. **Documentation** - Tests self-document which production code they cover
2. **IDE Navigation** - Jump from tests to production code
3. **Refactoring Safety** - Know which tests are affected when changing methods
4. **Test Discovery** - Find all tests covering a specific method
5. **Gap Detection** - Identify methods without test coverage

## Package Architecture

TestLink uses a two-package architecture:

| Package | Contains | Install As |
|---------|----------|------------|
| **test-attributes** | All PHP attributes (`#[TestedBy]`, `#[LinksAndCovers]`, `#[Links]`) | Production dependency |
| **testlink** | CLI tools, scanners, validators, Pest methods | Dev dependency |

::: tip
See the [Installation Guide](/introduction/installation) for detailed setup instructions and why this architecture matters.
:::
