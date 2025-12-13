# Why TDD with Links

Understanding the benefits of combining Test-Driven Development with bidirectional linking.

## The Traditional TDD Problem

TDD produces tests, but the relationship between tests and production code is implicit:

```php
// test file
public function test_creates_user(): void
{
    $service = new UserService();
    // ...
}

// production file
public function create(array $data): User
{
    // Which tests verify this? Who knows...
}
```

You have tests. They pass. But:
- How do you know `create()` is tested?
- Which tests verify it?
- What happens when you refactor?

## Links Solve the Visibility Problem

### Before: Hidden Relationships

```
Production                    Tests
──────────                    ─────
UserService::create()    ?    test_creates_user()
UserService::update()    ?    test_updates_user()
UserService::delete()    ?    (nothing?)
```

The `?` represents uncertainty. You need to search, read code, or hope.

### After: Explicit Relationships

```
Production                           Tests
──────────                           ─────
#[TestedBy(...)]                    ->linksAndCovers(...)
UserService::create()    ────────    test_creates_user()
UserService::update()    ────────    test_updates_user()
UserService::delete()    ────────    (validation fails!)
```

Relationships are declared, not discovered.

## Benefits During Development

### 1. Intentional Design

When you write the test first with a link, you're declaring intent:

```php
test('calculates discount for premium users', function () {
    // ...
})->linksAndCovers('@premium-discount');
```

This says: "I'm about to create something called premium-discount."

The placeholder becomes a design contract.

### 2. Immediate Feedback

After writing production code:

```bash
./vendor/bin/testlink pair --dry-run

@premium-discount will resolve to:
  Test: DiscountTest::calculates discount for premium users
  Prod: DiscountCalculator::calculatePremiumDiscount
```

You see the relationship immediately, not after searching.

### 3. Refactoring Safety

When you refactor:

```php
// Renamed from calculatePremiumDiscount to applyPremiumRate
public function applyPremiumRate(): float
```

TestLink tells you what broke:

```bash
./vendor/bin/testlink validate

✗ DiscountTest::calculates discount for premium users
  → linksAndCovers(DiscountCalculator::calculatePremiumDiscount)
  → Method not found (was it renamed?)
```

Without links, the test might still pass (hitting renamed code through other paths) while the intentional relationship is broken.

### 4. Documentation as You Code

TDD produces working code. Links add documentation:

```php
#[TestedBy(PriceCalculatorTest::class, 'calculates sum')]
#[TestedBy(PriceCalculatorTest::class, 'applies discount')]
#[TestedBy(PriceCalculatorTest::class, 'handles empty array')]
public function calculate(array $prices): int
```

Future developers see:
- The method is tested
- What aspects are covered
- Where to find the tests

This happens automatically during TDD, not as an afterthought.

## Benefits for the Codebase

### 1. Traceability Matrix

Every TDD cycle adds to a growing traceability matrix:

```bash
./vendor/bin/testlink report

PriceCalculator
├── calculate()
│   └── PriceCalculatorTest::calculates sum
├── applyDiscount()
│   ├── PriceCalculatorTest::applies percentage discount
│   └── PriceCalculatorTest::applies fixed discount
└── withTax()
    └── PriceCalculatorTest::adds tax to total
```

### 2. Coverage Confidence

Traditional coverage tools tell you lines were hit:

```
PriceCalculator.php: 95% coverage
```

Links tell you what was intentionally tested:

```
PriceCalculator::calculate - 1 test (explicit)
PriceCalculator::applyDiscount - 2 tests (explicit)
PriceCalculator::withTax - 1 test (explicit)
```

High coverage + explicit links = high confidence.

### 3. No Orphaned Tests

Tests that no longer test what they claim are dangerous:

```php
// This test SAYS it tests calculate()...
public function test_calculates_total(): void
{
    // But the actual assertion was removed in a refactor
    $calc = new Calculator();
    $calc->calculate([1, 2, 3]);
    $this->assertTrue(true);  // Oops
}
```

With links, validation catches this:

```bash
./vendor/bin/testlink validate --strict

⚠ test_calculates_total
  → Claims to cover Calculator::calculate
  → But method was modified without updating test
```

## Benefits for the Team

### 1. Code Review Clarity

PRs show the relationship:

```diff
+ #[TestedBy(OrderServiceTest::class, 'test_processes_payment')]
  public function processPayment(): void
  {
+     // new implementation
  }
```

Reviewers immediately know:
- This code has a test
- Which test verifies it
- Where to check the test

### 2. Onboarding

New developers understand code through its tests:

```php
// "What does this method do?"
#[TestedBy(CartServiceTest::class, 'test_adds_item')]
#[TestedBy(CartServiceTest::class, 'test_updates_quantity_if_exists')]
#[TestedBy(CartServiceTest::class, 'test_respects_max_quantity')]
public function addItem(Item $item): void
```

Reading the test names tells the story.

### 3. Consistent Practice

When TDD includes linking, the team builds:
- A habit of explicit relationships
- A codebase with full traceability
- Documentation that can't go stale

## The Compound Effect

Each TDD cycle:

```
RED → GREEN → REFACTOR → PAIR
```

Produces:
1. A passing test
2. Working production code
3. An explicit link
4. Updated traceability matrix

After 100 cycles, you have:
- 100 tests
- Production code they verify
- 100 explicit links
- A navigable, traceable codebase

## Comparison

| Aspect | TDD Alone | TDD + Links |
|--------|-----------|-------------|
| Tests exist | ✓ | ✓ |
| Code is tested | ✓ | ✓ |
| Relationships visible | ✗ | ✓ |
| IDE navigation | ✗ | ✓ |
| Refactoring safe | Partial | Full |
| Coverage meaningful | Lines hit | Intentional |
| Self-documenting | Limited | Comprehensive |

## Summary

TDD with links transforms testing from:

> "I wrote tests and they pass"

To:

> "I have proven, navigable, verifiable relationships between every test and the code it verifies"

The extra effort of adding links during TDD is minimal (especially with placeholders), but the benefits compound over time into a significantly more maintainable codebase.

## See Also

- [When to Add Links](./when-to-add-links)
- [Tutorial: Red-Green-Refactor](/tutorials/tdd/red-green-refactor)
- [Test Traceability](/explanation/test-traceability)
