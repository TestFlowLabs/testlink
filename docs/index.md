---
layout: home

hero:
  name: TestLink
  text: Bidirectional Test Linking
  tagline: Connect your tests to production code—and back. For Pest and PHPUnit.
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

## 🔗 Bidirectional Links

Production code knows its tests. Tests know what they cover. **Both stay in sync.**

Add `#[TestedBy]` to production methods to declare which tests verify them. Add links in your tests pointing back to production. TestLink validates that both sides match.

[Learn more →](/explanation/bidirectional-linking)

</div>
<div class="feature-code">

```php
// Production code declares its tests
class UserService
{
    #[TestedBy(UserServiceTest::class, 'creates user')]
    public function create(array $data): User
    {
        // ...
    }
}
```

```php
// Test declares what it covers
test('creates user', function () {
    $user = app(UserService::class)->create([...]);
    expect($user)->toBeInstanceOf(User::class);
})->linksAndCovers(UserService::class.'::create');
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## 🧪 Pest & PHPUnit

Use Pest's `->linksAndCovers()` chain or PHPUnit's `#[LinksAndCovers]` attribute. **Mix both in one project.**

Choose your style—TestLink supports all of them:

- **Pest** — Method chains
- **PHPUnit** — PHP 8 attributes
- **PHPUnit** — `@see` DocBlock tags

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

## ✅ Catch Broken Links

Renamed a method? Deleted a test? **Validation catches it instantly.**

Run `testlink validate` in CI to ensure all links are valid. No more orphaned `#[TestedBy]` attributes pointing to deleted tests.

[Set up CI validation →](/how-to/run-validation-in-ci)

</div>
<div class="feature-code">

```bash
$ ./vendor/bin/testlink validate

  Validation Report
  ─────────────────

  ✗ Orphan TestedBy
    App\UserService::create
      → UserServiceTest::deleted_test (test not found)

  ✗ Missing TestedBy
    UserServiceTest::creates_user
      → UserService::create (no #[TestedBy])

  Found 2 issue(s). Run sync to fix.
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## 🔄 Auto-Sync

Don't manually maintain both sides. **Let TestLink sync them for you.**

Add links in your tests, run `testlink sync`, and TestLink adds the corresponding `#[TestedBy]` attributes to production code automatically.

[Learn sync workflow →](/how-to/sync-links-automatically)

</div>
<div class="feature-code">

```bash
$ ./vendor/bin/testlink sync

  Syncing Coverage Links
  ──────────────────────

  Adding links
    ✓ UserService::create
      + #[TestedBy(UserServiceTest::class, 'creates user')]
      + #[TestedBy(UserServiceTest::class, 'validates email')]

  Modified 1 file(s). Added 2 link(s).
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## ⚡ TDD/BDD Placeholders

Write tests before classes exist. **Use `@placeholder` markers, resolve them later.**

During rapid TDD, you don't know the final class name yet. Use placeholders like `@user-create` in both test and production code. Run `testlink pair` when ready.

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

  ✓ @discount  1 production × 1 test = 1 link

  Resolved 1 placeholder. Modified 2 file(s).
```

</div>
</div>

<div class="feature-section">
<div class="feature-text">

## 🧭 IDE Navigation

Click `@see` tags to jump between tests and production code. **PHPStorm, VS Code, and more.**

TestLink adds `@see` tags that IDEs recognize. Ctrl+Click to navigate instantly. No more searching for related tests.

[Configure IDE →](/how-to/setup-ide-navigation)

</div>
<div class="feature-code">

```php
class OrderService
{
    /**
     * @see \Tests\OrderServiceTest::test_creates_order
     * @see \Tests\OrderServiceTest::test_validates_items
     * @see \Tests\OrderFlowTest::test_complete_checkout
     */
    #[TestedBy(OrderServiceTest::class, 'test_creates_order')]
    public function create(array $items): Order
    {
        // Ctrl+Click any @see tag to jump to the test
    }
}
```

</div>
</div>

</div>
