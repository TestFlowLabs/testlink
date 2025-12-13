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

features:
  - icon: 🔗
    title: Bidirectional Links
    details: Production code knows its tests. Tests know what they cover. Both stay in sync.
    link: /explanation/bidirectional-linking
  - icon: 🧪
    title: Pest & PHPUnit
    details: Use Pest's ->linksAndCovers() or PHPUnit's #[LinksAndCovers]. Mix both in one project.
    link: /reference/pest-methods
  - icon: ✅
    title: Catch Broken Links
    details: Renamed a method? Deleted a test? Validation catches orphaned and missing links instantly.
    link: /how-to/run-validation-in-ci
  - icon: 🔄
    title: Auto-Sync
    details: Generate missing links automatically. From #[TestedBy] to tests, or tests to production.
    link: /how-to/sync-links-automatically
  - icon: ⚡
    title: TDD/BDD Placeholders
    details: Write tests before classes exist. Use @placeholder markers, resolve them later with testlink pair.
    link: /explanation/placeholder-strategy
  - icon: 🧭
    title: IDE Navigation
    details: Click @see tags to jump between tests and production code. PHPStorm, VS Code, and more.
    link: /how-to/setup-ide-navigation
---

::: code-group

```php [Production]
<?php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    /**
     * @see \Tests\UserServiceTest::creates a user
     * @see \Tests\UserServiceTest::validates email
     * @see \Tests\UserFlowTest::complete registration
     */
    #[TestedBy(UserServiceTest::class, 'creates a user')]
    #[TestedBy(UserServiceTest::class, 'validates email')]
    #[TestedBy(UserFlowTest::class, 'complete registration')]
    public function create(array $data): User
    {
        // Click any @see tag to jump to the test
    }
}
```

```php [Tests (Pest)]
/**
 * @see \App\Services\UserService::create
 */
test('creates a user', function () {
    $user = app(UserService::class)->create([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    expect($user)->toBeInstanceOf(User::class);
})->linksAndCovers(UserService::class.'::create');

/**
 * @see \App\Services\UserService::create
 */
test('validates email', function () {
    // ...
})->linksAndCovers(UserService::class.'::create');

// Integration test - links without coverage
test('complete registration', function () {
    // ...
})->links(UserService::class.'::create');
```

```php [Tests (PHPUnit)]
use TestFlowLabs\TestingAttributes\LinksAndCovers;
use TestFlowLabs\TestingAttributes\Links;

class UserServiceTest extends TestCase
{
    /**
     * @see \App\Services\UserService::create
     */
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_a_user(): void
    {
        // Unit test with coverage
    }

    /**
     * @see \App\Services\UserService::create
     */
    #[Links(UserService::class, 'create')]
    public function test_complete_registration(): void
    {
        // Integration test - link only, no coverage
    }
}
```

```php [Placeholders]
// TDD: Write test BEFORE the class exists!

test('calculates discount', function () {
    $calc = new PriceCalculator();
    expect($calc->calculate(100, 0.1))->toBe(90);
})->linksAndCovers('@price-calc');

// Production code (written after test)
#[TestedBy('@price-calc')]
public function calculate(int $price, float $discount): int
{
    return (int) ($price * (1 - $discount));
}

// Later, resolve placeholders to real references:
// $ ./vendor/bin/testlink pair
```

```bash [CLI]
$ ./vendor/bin/testlink report

  Coverage Links Report
  ─────────────────────

  App\Services\UserService
    create()
    → UserServiceTest::creates a user
    → UserServiceTest::validates email
    → UserFlowTest::complete registration

  Summary
    Methods with tests: 1
    Total test links: 3

$ ./vendor/bin/testlink validate

  Validation Report
  ─────────────────

  Link Summary
    Pest method chain links: 3
    Total links: 3

  All links are valid!

$ ./vendor/bin/testlink sync --dry-run

  Syncing Coverage Links
  ──────────────────────
  Running in dry-run mode. No files will be modified.

  Would add @see tags to
    ✓ UserService::create
      + @see UserServiceTest::creates a user

  Would add 1 @see tag(s).

$ ./vendor/bin/testlink pair

  Pairing Placeholders
  ────────────────────

  Found Placeholders
    ✓ @price-calc  1 production × 1 tests = 1 links

  Pairing complete. Modified 2 file(s) with 2 change(s).
```

:::

## Synchronized Tabs Demo

Select your stack once — all code examples across the documentation will update to match your choice.

### Production Code

:::tabs key:stack
== Pest

```php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'it creates a user')]
    #[TestedBy(UserServiceTest::class, 'it validates email')]
    public function create(array $data): User
    {
        // Your production code
    }
}
```

== PHPUnit + Attributes

```php
use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'test_create')]
    #[TestedBy(UserServiceTest::class, 'test_validates_email')]
    public function create(array $data): User
    {
        // Your production code
    }
}
```

== PHPUnit + @see

```php
class UserService
{
    /**
     * @see \Tests\UserServiceTest::test_create
     * @see \Tests\UserServiceTest::test_validates_email
     */
    public function create(array $data): User
    {
        // Your production code
    }
}
```

:::

### Test Code

:::tabs key:stack
== Pest

```php
it('creates a user', function () {
    $user = app(UserService::class)->create([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    expect($user)->toBeInstanceOf(User::class);
})->linksAndCovers(UserService::class, 'create');

it('validates email', function () {
    // ...
})->linksAndCovers(UserService::class, 'create');
```

== PHPUnit + Attributes

```php
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_create(): void
    {
        $user = app(UserService::class)->create([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
    }

    #[LinksAndCovers(UserService::class, 'create')]
    public function test_validates_email(): void
    {
        // ...
    }
}
```

== PHPUnit + @see

```php
class UserServiceTest extends TestCase
{
    /**
     * @see \App\Services\UserService::create
     */
    public function test_create(): void
    {
        $user = app(UserService::class)->create([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
    }

    /**
     * @see \App\Services\UserService::create
     */
    public function test_validates_email(): void
    {
        // ...
    }
}
```

:::
