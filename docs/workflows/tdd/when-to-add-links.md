# When to Add Links

Understanding the optimal moments in the TDD cycle to add bidirectional links.

## The TDD Cycle Revisited

```
┌───────────────────────────────────────────────────────┐
│                                                       │
│   RED          GREEN          REFACTOR       PAIR    │
│   │            │              │              │        │
│   Write test   Make it pass   Clean up       Resolve  │
│   ↓            ↓              ↓              ↓        │
│   Add @A       Add @A         Keep links     @A→real  │
│                                                       │
└───────────────────────────────────────────────────────┘
```

Links can be added at different points. Each has trade-offs.

## Option 1: RED Phase (Recommended)

Add the link when writing the test:

```php
// RED: Test doesn't pass yet, class doesn't exist
test('calculates discount', function () {
    $calc = new DiscountCalculator();
    expect($calc->calculate(100, 0.1))->toBe(90);
})->linksAndCovers('@discount');  // Add link immediately
```

### Why RED Phase?

**Intent Documentation**: The link documents your design intent before implementation.

```php
->linksAndCovers('@order-validation')
// "I intend to create something that validates orders"
```

**No Extra Step**: Link is part of writing the test, not a separate task.

**Placeholder Works**: Since the class doesn't exist, placeholders are natural:

```php
// Class doesn't exist yet - no problem!
->linksAndCovers('@calculator')
```

**Matches TDD Philosophy**: Test-first means link-first.

### RED Phase Workflow

```php
// 1. Write test with placeholder
test('processes refund', function () {
    $service = new PaymentService();
    $result = $service->refund(100);
    expect($result)->toBeTrue();
})->linksAndCovers('@payment-refund');

// 2. Run test - it fails (RED)
./vendor/bin/pest
// FAIL: Class PaymentService not found

// 3. Continue to GREEN phase...
```

## Option 2: GREEN Phase

Add links when writing production code:

```php
// GREEN: Making the test pass
#[TestedBy('@payment-refund')]  // Add during implementation
public function refund(int $amount): bool
{
    // implementation
}
```

### Why GREEN Phase?

**Both Sides Together**: You're actively thinking about the test-production relationship.

**Confirms Understanding**: Adding the link confirms you know which test you're satisfying.

```php
// "I'm writing this because test X needs it"
#[TestedBy('@payment-refund')]
public function refund(int $amount): bool
```

### GREEN Phase Workflow

```php
// 1. Test already exists (from RED)
test('processes refund', function () { ... })
    ->linksAndCovers('@payment-refund');

// 2. Write production code with link
#[TestedBy('@payment-refund')]
public function refund(int $amount): bool
{
    return true; // Minimal to pass
}

// 3. Test passes (GREEN)
```

## Option 3: REFACTOR Phase (Not Recommended)

Adding links during refactoring:

```php
// REFACTOR: Code is clean, now add links
// Not recommended - easy to forget
```

### Why Not REFACTOR Phase?

**Separate Concern**: Refactoring should focus on code quality, not documentation.

**Easy to Skip**: "I'll add links later" often becomes "never."

**Lost Context**: By refactoring time, you may forget the exact relationship.

## Option 4: PAIR Phase

Resolve placeholders to real references:

```bash
./vendor/bin/testlink pair
```

### When is PAIR Phase?

After completing a RED-GREEN-REFACTOR cycle (or several):

```php
// Before PAIR
test('...')>linksAndCovers('@A');

#[TestedBy('@A')]
public function method(): void

// After PAIR
test('...')->linksAndCovers(PaymentService::class.'::refund');

#[TestedBy(PaymentServiceTest::class, 'processes refund')]
public function refund(): bool
```

### Timing Considerations

**After Each Cycle**: Resolve immediately while context is fresh.

```
RED → GREEN → REFACTOR → PAIR → commit
```

**After Several Cycles**: Batch resolution for rapid iteration.

```
RED → GREEN → REFACTOR →
RED → GREEN → REFACTOR →
RED → GREEN → REFACTOR → PAIR → commit
```

**Before Commit**: Never commit unresolved placeholders.

```bash
# Pre-commit hook
./vendor/bin/testlink pair
git add .
git commit -m "feat: add payment refund"
```

## Recommended Workflow

### For Single Features

```php
// 1. RED: Write test with placeholder
test('validates email format', function () {
    // ...
})->linksAndCovers('@email-validator');

// 2. GREEN: Write production with same placeholder
#[TestedBy('@email-validator')]
public function validateEmail(string $email): bool
{
    // ...
}

// 3. REFACTOR: Clean up code (links already in place)

// 4. PAIR: Resolve placeholder
./vendor/bin/testlink pair

// 5. Commit
```

### For Multiple Related Tests

```php
// 1. RED: Write all tests
test('validates required fields', fn() => ...)->linksAndCovers('@A');
test('validates email format', fn() => ...)->linksAndCovers('@A');
test('validates unique email', fn() => ...)->linksAndCovers('@A');

// 2. GREEN: Write production
#[TestedBy('@A')]
public function validate(array $data): bool
{
    // ...
}

// 3. PAIR: One placeholder → multiple links
./vendor/bin/testlink pair

// Result: All three tests link to validate()
```

## Link Timing Summary

| Phase | Add Link? | Why |
|-------|-----------|-----|
| RED | ✓ Test side | Documents intent |
| GREEN | ✓ Production side | Confirms relationship |
| REFACTOR | ✗ | Focus on code quality |
| PAIR | ✓ Resolve | Convert @ to real |

## When to Use Real References vs Placeholders

### Use Placeholders (@) When

- Class doesn't exist yet (true TDD)
- Rapid iteration, will resolve soon
- Exploring design, names may change

```php
->linksAndCovers('@new-feature')
```

### Use Real References When

- Class already exists
- Adding to existing codebase
- Reference won't change

```php
->linksAndCovers(ExistingService::class.'::method')
```

## Common Mistakes

### Adding Links Too Late

```php
// Mistake: Write test, production, tests pass, forget links
test('does something', function () { ... });  // No link!

public function doSomething(): void { ... }    // No TestedBy!

// Fix: Add during RED/GREEN phases
```

### Never Resolving Placeholders

```php
// Mistake: Placeholders become permanent
->linksAndCovers('@A')  // Been like this for months

// Fix: Resolve before commit
./vendor/bin/testlink pair
```

### Adding Links Without Tests

```php
// Mistake: Production has TestedBy but test doesn't exist
#[TestedBy(SomeTest::class, 'test_that_doesnt_exist')]

// Fix: Validate catches this
./vendor/bin/testlink validate
```

## Integration with CI

Enforce proper timing:

```yaml
# .github/workflows/ci.yml
- run: ./vendor/bin/testlink pair
  # Fails if unresolved placeholders

- run: ./vendor/bin/testlink validate
  # Fails if links are broken
```

## Summary

The optimal timing:
1. **RED**: Add placeholder to test (`->linksAndCovers('@A')`)
2. **GREEN**: Add placeholder to production (`#[TestedBy('@A')]`)
3. **REFACTOR**: Keep links, focus on code quality
4. **PAIR**: Resolve placeholders before commit

This ensures links are always present, accurately represent relationships, and stay synchronized.

## See Also

- [Why TDD with Links](./why-tdd-with-links)
- [Placeholder Strategy](/explanation/placeholder-strategy)
- [Tutorial: Placeholder TDD](/tutorials/tdd/placeholder-tdd)
