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
     * @see \Tests\UserServiceTest::test_creates_user      ← Cmd+Click
     * @see \Tests\UserServiceTest::test_validates_email   ← Cmd+Click
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
 * @see \Tests\OrderServiceTest::test_creates_order
 * @see \Tests\OrderServiceTest::test_validates_items
 * @see \Tests\OrderServiceTest::test_calculates_total
 * @see \Tests\OrderFlowTest::test_complete_checkout
 * @see \Tests\OrderFlowTest::test_payment_flow
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
    → OrderServiceTest::test_creates_order
    → OrderServiceTest::test_validates_items
    → OrderServiceTest::test_calculates_total
    → OrderFlowTest::test_complete_checkout
    → OrderFlowTest::test_payment_flow
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
#[LinksAndCovers(UserService::class, 'create')]
public function test_creates_user(): void
{
    // ...
}
```

== PHPUnit + @see

```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
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

  ✗ Broken link
    UserService::create
      → UserServiceTest::test_old_name (test not found)

  ✗ Missing link
    UserServiceTest::test_creates_user
      → UserService::create (no @see in production)

  Found 2 issue(s). Run sync to fix.
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## 🔄 Auto-Sync & Placeholders

Don't manually maintain links. **Sync generates them automatically.**

Writing tests before classes exist? Use `@placeholder` markers during TDD, resolve them later.

[Sync workflow →](/how-to/sync-links-automatically) · [Placeholders →](/explanation/placeholder-strategy)

</div>
<div class="feature-code">

```bash
$ ./vendor/bin/testlink sync

  Syncing Links
  ─────────────

  Adding @see tags
    ✓ UserService::create
      + @see UserServiceTest::test_creates_user
      + @see UserServiceTest::test_validates_email

  Modified 1 file(s). Added 2 link(s).
```

```bash
$ ./vendor/bin/testlink pair

  Resolving Placeholders
  ──────────────────────

  ✓ @user-create  1 production × 2 tests = 2 links

  Resolved 1 placeholder. Modified 3 file(s).
```

</div>
</div>

</div>
