---
layout: home

hero:
  name: TestLink
  text: Test Coverage Traceability
  tagline: Framework-agnostic test traceability for PHP.
  image:
    src: /testlink-logo.png
    alt: TestLink Logo
  actions:
    - theme: brand
      text: Get Started
      link: /introduction/what-is-testlink
    - theme: alt
      text: View on GitHub
      link: https://github.com/testflowlabs/testlink

features:
  - icon: 🔗
    title: Bidirectional Links
    details: Link tests to production and production to tests. TestedBy on code, LinksAndCovers on tests.
  - icon: 🎯
    title: Framework Agnostic
    details: Works with Pest, PHPUnit, or both in the same project. Use your preferred testing framework.
  - icon: ✅
    title: Strict Validation
    details: Ensure bidirectional links are synchronized. Catch mismatches before they reach production.
  - icon: 🔄
    title: Bidirectional Sync
    details: Generate TestedBy from tests or LinksAndCovers from production code automatically.
---

## Quick Example

### Production Code

Mark production methods with the tests that cover them:

```php
<?php

namespace App\Services;

use TestFlowLabs\TestingAttributes\TestedBy;

class UserService
{
    #[TestedBy(UserServiceTest::class, 'test_creates_a_new_user')]
    #[TestedBy(UserServiceTest::class, 'test_validates_user_email')]
    public function create(array $data): User
    {
        // Implementation
    }
}
```

### Test Code

Link your tests to production methods:

::: code-group

```php [Pest]
test('creates a new user', function () {
    $user = app(UserService::class)->create([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    expect($user)->toBeInstanceOf(User::class);
})->linksAndCovers(UserService::class.'::create');

test('validates user email', function () {
    expect(fn () => app(UserService::class)->create([
        'email' => 'invalid',
    ]))->toThrow(ValidationException::class);
})->linksAndCovers(UserService::class.'::create');
```

```php [PHPUnit]
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_creates_a_new_user(): void
    {
        $user = app(UserService::class)->create([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
    }

    #[LinksAndCovers(UserService::class, 'create')]
    public function test_validates_user_email(): void
    {
        $this->expectException(ValidationException::class);

        app(UserService::class)->create([
            'email' => 'invalid',
        ]);
    }
}
```

:::

### CLI Commands

Use the standalone CLI that works with any framework:

::: code-group

```bash [Validate Links]
$ testlink validate

Validation Report:
  ✓ All links are valid!

  PHPUnit attribute links: 2
  Pest method chain links: 0
  Total links: 2
```

```bash [Generate Report]
$ testlink report

Coverage Links Report
─────────────────────

UserService::create
  → UserServiceTest::test_creates_a_new_user
  → UserServiceTest::test_validates_user_email

Summary:
  Methods: 1
  Tests: 2
```

:::

## Part of the TestFlowLabs Ecosystem

TestLink works seamlessly with other TestFlowLabs packages:

- **[test-attributes](https://github.com/testflowlabs/test-attributes)** - PHP attributes for test metadata (`#[LinksAndCovers]`, `#[Links]`, etc.)
- **[pest-plugin-bdd](https://github.com/testflowlabs/pest-plugin-bdd)** - BDD testing with Gherkin feature files

Together, these packages provide a complete testing workflow from behavior specification to implementation validation.
