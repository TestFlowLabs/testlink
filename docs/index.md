---
layout: home

hero:
  name: TestLink
  text: Test Coverage Traceability
  tagline: Framework-agnostic test-to-code linking for PHP. Works with Pest and PHPUnit.
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
    details: Create explicit links between production code and tests using TestedBy and LinksAndCovers attributes.
  - icon: 🎯
    title: Framework Agnostic
    details: Works seamlessly with Pest, PHPUnit, or both in the same project. No lock-in to a single framework.
  - icon: ✅
    title: Link Validation
    details: Validate that all links are synchronized and catch broken references before they reach production.
  - icon: 🔄
    title: Auto-Sync
    details: Automatically generate missing links in either direction. Keep production and test code in sync.
  - icon: 🚀
    title: CI/CD Ready
    details: JSON output, exit codes, and strict mode for seamless integration with GitHub Actions, GitLab CI, and more.
  - icon: 🛠️
    title: Standalone CLI
    details: Simple command-line interface with report, validate, sync, and pair commands. No runtime overhead.
---

::: code-group

```php [Production Code]
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

```php [Test Code (Pest)]
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

```php [Test Code (PHPUnit)]
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

```bash [CLI: Validate]
$ testlink validate

Validation Report:
  ✓ All links are valid!

  PHPUnit attribute links: 2
  Pest method chain links: 0
  Total links: 2
```

```bash [CLI: Report]
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