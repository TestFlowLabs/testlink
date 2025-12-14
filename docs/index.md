---
layout: home

hero:
  name: TestLink
  text: Navigate Tests ↔ Production
  tagline: Cmd+Click to jump between your tests and production code. Instantly.
  image:
    src: /testlink-logo.svg
    alt: TestLink Logo
  actions:
    - theme: brand
      text: Get Started
      link: /tutorials/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/testflowlabs/testlink
---

<div class="feature-sections">

<div class="feature-section">
<div class="feature-text">

## 🔗 Click to Navigate

**No more searching for tests.** Cmd+Click any `@see` tag to jump directly to the related code.

From production code, click to see which tests verify it. From tests, click to see which production code they cover. Both directions, instantly navigable.

[How it works →](/explanation/bidirectional-linking)

</div>
<div class="feature-code">

```php
class UserService
{
    /**
     * @see \Tests\UserServiceTest::creates_user      ← Cmd+Click
     * @see \Tests\UserServiceTest::validates_email   ← Cmd+Click
     */
    public function create(array $data): User
    {
        // Which tests verify this method? Just click above.
    }
}
```

```php
/**
 * @see \App\Services\UserService::create   ← Cmd+Click
 */
test('creates user', function () {
    // What does this test cover? Just click above.
});
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## 📊 See All Relationships

One method tested by 5 different tests? **See them all at a glance.**

One test covers multiple methods? Visible instantly. No more guessing which code is tested, or which tests cover what.

[Understanding reports →](/tutorials/understanding-reports)

</div>
<div class="feature-code">

```php
/**
 * @see \Tests\OrderServiceTest::creates_order
 * @see \Tests\OrderServiceTest::validates_items
 * @see \Tests\OrderServiceTest::calculates_total
 * @see \Tests\OrderFlowTest::complete_checkout
 * @see \Tests\OrderFlowTest::payment_flow
 */
public function create(array $items): Order
{
    // 5 tests verify this method - all visible here
}
```

```bash
$ ./vendor/bin/testlink report

  OrderService
    create()
    → OrderServiceTest::creates_order
    → OrderServiceTest::validates_items
    → OrderServiceTest::calculates_total
    → OrderFlowTest::complete_checkout
    → OrderFlowTest::payment_flow

  Summary
  ───────
    Methods with tests:       1
    Total test links:         5
    @see tags:                0

  ✓ Report complete.
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## 🧪 Pest & PHPUnit

Works with your existing framework. **Pest method chains, PHPUnit attributes, or `@see` tags.**

Mix all three in the same project. TestLink recognizes them all.

[See all methods →](/reference/pest-methods)

</div>
<div class="feature-code">

:::tabs key:stack
== Pest

```php
test('creates user', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');
```

== PHPUnit + Attributes

```php
#[Test]
#[LinksAndCovers(UserService::class, 'create')]
public function creates_user(): void
{
    // ...
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\UserService::create
 */
#[Test]
public function creates_user(): void
{
    // ...
}
```

:::

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## ✅ Keep Links Valid

Renamed a method? Deleted a test? **Validation catches broken links instantly.**

Run in CI/CD to ensure your navigation links stay accurate as code evolves.

[Set up CI validation →](/how-to/run-validation-in-ci)

</div>
<div class="feature-code">

```bash
$ ./vendor/bin/testlink validate

  Validation Report
  ─────────────────

  Orphan @see Tags
    ✗ UserServiceTest::old_name
      → src/UserService.php:15

  Summary
  ───────
    PHPUnit attribute links:  10
    Pest method chain links:  5
    @see tags:                8
    Total links:              23

    Issues found:             1
      Orphan @see tags:       1

  ✓ Validation complete with issues.
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## 🔄 Auto-Sync

Don't manually maintain links. **Sync generates them bidirectionally.**

Add a link on **either side**—production or tests—and `testlink sync` propagates it to the other side. Start from whichever side feels natural for your workflow.

[Sync workflow →](/how-to/sync-links-automatically)

</div>
<div class="feature-code">

```bash
$ ./vendor/bin/testlink sync

  Syncing Coverage Links
  ──────────────────────

  Modified Files
    ✓ src/Services/UserService.php (1 change)
      + #[TestedBy(UserServiceTest::class, 'creates_user')]

    ✓ tests/Unit/OrderServiceTest.php (1 change)
      + linksAndCovers(OrderService::class.'::process')

  Summary
  ───────
    Files modified:           2
    Files pruned:             0
    @see tags added:          0
    @see tags removed:        0
    #[TestedBy] added:        1

  ✓ Sync complete.
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## ⚡ TDD Placeholders

Writing tests before classes exist? **Use `@placeholder` markers.**

During rapid TDD, you don't know the final class name yet. Use placeholders like `@user-create` in both test and production code, then resolve them with `testlink pair`.

[Placeholder strategy →](/explanation/placeholder-strategy)

</div>
<div class="feature-code">

```php
// Test written BEFORE the class exists
test('calculates discount', function () {
    $calc = new PriceCalculator();
    expect($calc->calculate(100, 0.1))->toBe(90);
})->linksAndCovers('@discount');

// Production code (written after test passes)
#[TestedBy('@discount')]
public function calculate(int $price, float $discount): int
{
    return (int) ($price * (1 - $discount));
}
```

```bash
$ ./vendor/bin/testlink pair

  Pairing Placeholders
  ────────────────────

  Found Placeholders
    ✓ @discount  1 production × 1 tests = 1 links

  Summary
  ───────
    Placeholders resolved:    1
    Total changes:            2
    Files modified:           2

  ✓ Pairing complete.
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## 🔧 Auto-Fix @see Tags

Short class names in `@see` tags? **Fix them automatically.**

TestLink detects non-FQCN references and resolves them using your `use` statements. One command converts `UserService::create` to `\App\Services\UserService::create`.

[Using @see tags →](/how-to/use-see-tags)

</div>
<div class="feature-code">

```php
// Before: Short class name (IDE can't navigate)
/**
 * @see UserServiceTest::creates_user
 */
public function create(): User { }

// After: FQCN (Cmd+Click works!)
/**
 * @see \Tests\Unit\UserServiceTest::creates_user
 */
public function create(): User { }
```

```bash
$ ./vendor/bin/testlink validate --fix

  Validation Report
  ─────────────────

  FQCN Conversion Results
    ✓ src/TestLink/UserService.php
      + Tests\TestLink\UserServiceTest::creates
        → \Tests\TestLink\UserServiceTest::creates

  ✓ Converted 1 @see tag(s) in 1 file(s).

  Summary
  ───────
    PHPUnit attribute links:  5
    Pest method chain links:  3
    @see tags:                4
    Total links:              12

    Issues fixed:             1

  ✓ Validation complete. All links are valid!
```

</div>
</div>

</div>
