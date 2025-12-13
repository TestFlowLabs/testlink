# Test Traceability

Test traceability is the foundation for **Cmd+Click navigation** between tests and production code. It's what makes your codebase navigable—click any `@see` tag to jump to related code.

## What is Test Traceability?

Test traceability is the ability to trace relationships between:

- **Requirements** → What should the software do?
- **Production Code** → How does the software do it?
- **Tests** → How do we verify it works?

```
Requirement: "Users can create accounts"
        │
        ▼
Production: UserService::create()
        │
        ▼
Tests: test_creates_user, test_validates_email, ...
```

Traceability answers questions like:
- Which tests verify this requirement?
- What code implements this feature?
- If this test fails, what's broken?
- Is this requirement tested?

## Why Traceability Matters

### 1. Confidence in Changes

When modifying code, you need to know:

```php
// I want to refactor this method
public function calculateTotal(): float
{
    // Which tests should I run?
    // What behavior am I expected to preserve?
}
```

With traceability:

```php
#[TestedBy(OrderServiceTest::class, 'test_calculates_total')]
#[TestedBy(OrderServiceTest::class, 'test_applies_discount')]
#[TestedBy(OrderServiceTest::class, 'test_handles_empty_cart')]
public function calculateTotal(): float
{
    // Now I know exactly what's tested
}
```

### 2. Gap Detection

Without traceability:

```php
class PaymentService
{
    public function charge(): void { }     // Tested?
    public function refund(): void { }     // Maybe tested?
    public function verify(): void { }     // Probably tested?
}
```

With traceability:

```bash
./vendor/bin/testlink report

PaymentService
├── charge()
│   └── PaymentServiceTest::test_charges_card
├── refund()
│   └── (no tests)  ← Gap!
└── verify()
    └── PaymentServiceTest::test_verifies_payment
```

### 3. Impact Analysis

When a test fails:

```
FAILED: test_processes_order
```

Without traceability: "What does this even test?"

With traceability:

```php
/**
 * @see \App\Services\OrderService::process
 * @see \App\Services\OrderService::validate
 */
public function test_processes_order(): void
```

Now you know exactly which production code to investigate.

### 4. Regulatory Compliance

Many industries require proof that code is tested:

- Medical devices (FDA)
- Automotive (ISO 26262)
- Aviation (DO-178C)
- Finance (SOX)

TestLink provides auditable traceability:

```bash
./vendor/bin/testlink report --json > traceability-matrix.json
```

### 5. Onboarding

New team members can understand the codebase:

```php
// What does this method do? Read the tests!
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
#[TestedBy(UserServiceTest::class, 'test_validates_unique_email')]
#[TestedBy(UserServiceTest::class, 'test_hashes_password')]
public function create(array $data): User
```

The attributes tell you:
- User creation involves email validation
- Passwords are hashed
- Three distinct behaviors are tested

## How TestLink Implements Traceability

### Bidirectional Links

TestLink creates links in both directions:

:::tabs key:stack
== Pest

```
Production                          Test
──────────                          ────
#[TestedBy(Test, 'method')]   ←→   ->linksAndCovers(Prod::method)
```

== PHPUnit + Attributes

```
Production                          Test
──────────                          ────
#[TestedBy(Test, 'method')]   ←→   #[LinksAndCovers(Prod, 'method')]
```

== PHPUnit + @see

```
Production                          Test
──────────                          ────
@see Test::method             ←→   @see Prod::method
```

:::

This ensures:
- Every production method knows its tests
- Every test knows what it covers
- Both sides stay synchronized

### Attribute-Based

Links are declared in code, not external documents:

```php
// Not in a spreadsheet or wiki
// Right here in the code

#[TestedBy(UserServiceTest::class, 'test_creates_user')]
public function create(): User
```

Benefits:
- Links travel with the code
- Version controlled
- Refactoring tools can update them
- No sync with external systems

### Machine-Verifiable

TestLink validates that links are correct:

```bash
./vendor/bin/testlink validate

✓ All links synchronized
  - 45 production methods linked
  - 78 tests verified
  - 0 orphans
```

Unlike comments or documentation, these links are verified.

### Navigable Code (Primary Benefit)

The main reason for traceability: **Cmd+Click to jump between tests and production**.

Links generate `@see` tags that IDEs understand:

```php
/**
 * @see \Tests\UserServiceTest::test_creates_user   ← Cmd+Click
 */
public function create(): User
```

Click any `@see` tag and your IDE jumps directly to that code. No searching, no guessing.

## Traceability Matrix

TestLink can generate a traceability matrix:

```bash
./vendor/bin/testlink report --json
```

```json
{
  "coverage": [
    {
      "class": "App\\Services\\UserService",
      "method": "create",
      "tests": [
        {
          "class": "Tests\\UserServiceTest",
          "method": "test_creates_user",
          "type": "covers"
        },
        {
          "class": "Tests\\UserFlowTest",
          "method": "test_registration",
          "type": "links"
        }
      ]
    }
  ]
}
```

This matrix shows:
- Every production method
- All tests that cover or link to it
- The type of relationship

## Levels of Traceability

### Method-Level (Detailed)

```php
#[TestedBy(CalculatorTest::class, 'test_adds_numbers')]
public function add(int $a, int $b): int

#[TestedBy(CalculatorTest::class, 'test_subtracts_numbers')]
public function subtract(int $a, int $b): int
```

Most granular. Each method has specific tests.

### Class-Level (Broader)

```php
#[TestedBy(CalculatorTest::class)]  // No method = class level
public function add(int $a, int $b): int
```

The entire test class covers this method.

### Mixed

```php
// Primary unit test - method level
#[TestedBy(UserServiceTest::class, 'test_creates_user')]
// Integration test - class level
#[TestedBy(UserFlowTest::class)]
public function create(): User
```

## Traceability vs Coverage

| Concept | Traceability | Coverage |
|---------|-------------|----------|
| Question | Which tests verify this? | Is this code executed? |
| Measured | Explicit links | Runtime execution |
| Guarantees | Test exists | Line was hit |
| Intentional | Yes, developer declares | No, automatic |

### Coverage Alone Isn't Enough

```php
public function divide(int $a, int $b): float
{
    return $a / $b;  // 100% coverage if any test calls this
}
```

But does any test verify the divide-by-zero case?

Coverage tools say "yes, covered" but don't tell you **what** is tested.

### Traceability Tells the Story

```php
#[TestedBy(CalculatorTest::class, 'test_divides_numbers')]
#[TestedBy(CalculatorTest::class, 'test_throws_on_zero')]
public function divide(int $a, int $b): float
```

Now you know both normal and error cases are intentionally tested.

## Building Traceability Culture

### Start Small

Don't trace everything at once:

```bash
# Start with critical code
./vendor/bin/testlink sync --path=src/Services/Payment
```

### Use CI

Prevent traceability from degrading:

```yaml
- run: ./vendor/bin/testlink validate
```

### Make It Easy

Use placeholders for TDD:

```php
->linksAndCovers('@payment')  // Quick to write

// Resolve later
./vendor/bin/testlink pair
```

### Review in PRs

Check that new code includes links:

```php
// PR adds new method
public function processRefund(): void  // Where's the TestedBy?
```

## Traceability Benefits Summary

| Benefit | Without Traceability | With Traceability |
|---------|---------------------|-------------------|
| Find tests for code | Search codebase | Look at attributes |
| Know what test covers | Read test code | Look at linksAndCovers |
| Detect untested code | Coverage gaps | Report shows gaps |
| Refactor safely | Hope tests exist | Know tests exist |
| Onboard developers | Ask someone | Read the links |
| Audit compliance | Manual documentation | Automated report |

## Summary

Test traceability exists for one primary purpose: **Cmd+Click navigation between tests and production code.**

TestLink implements traceability through:
- `@see` tags that IDEs recognize as clickable links
- PHP 8 attributes (`#[TestedBy]`, `#[LinksAndCovers]`)
- Pest method chains (`->linksAndCovers()`)
- Validation to ensure links stay accurate

The result: click any `@see` tag to jump directly to the related code. No searching, no guessing. Just click.

## See Also

- [Bidirectional Linking](./bidirectional-linking)
- [Links vs LinksAndCovers](./links-vs-linksandcovers)
- [CLI: report Command](/reference/cli/report)
- [How-to: Run Validation in CI](/how-to/run-validation-in-ci)
