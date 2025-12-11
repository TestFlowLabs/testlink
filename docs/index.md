---
layout: home

hero:
  name: TestLink
  text: Test Coverage Traceability
  tagline: Framework-agnostic test-to-code linking for PHP. Works with Pest and PHPUnit.
  image:
    src: /testlink-logo.svg
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
    link: /guide/test-coverage-links
  - icon: 🎯
    title: Framework Agnostic
    details: Works seamlessly with Pest, PHPUnit, or both in the same project. No lock-in to a single framework.
    link: /guide/covers-method-helper
  - icon: ✅
    title: Link Validation
    details: Validate that all links are synchronized and catch broken references before they reach production.
    link: /guide/validation
  - icon: 🔄
    title: Auto-Sync
    details: Automatically generate missing links in either direction. Keep production and test code in sync.
    link: /sync/
  - icon: 🚀
    title: CI/CD Ready
    details: JSON output, exit codes, and strict mode for seamless integration with GitHub Actions, GitLab CI, and more.
    link: /best-practices/ci-integration
  - icon: 🛠️
    title: Standalone CLI
    details: Simple command-line interface with report, validate, sync, and pair commands. No runtime overhead.
    link: /guide/cli-commands
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

```bash [CLI: report]
$ testlink report

  Coverage Links Report
  ─────────────────────

  App\Services\UserService

    create()
      → Tests\Unit\UserServiceTest::test_creates_a_new_user
      → Tests\Unit\UserServiceTest::test_validates_user_email

  Summary
    Methods with tests: 1
    Total test links: 2
```

```bash [CLI: validate]
$ testlink validate

  Validation Report
  ─────────────────

  Link Summary
    PHPUnit attribute links: 2
    Pest method chain links: 0
    Total links: 2

  ✓ All links are valid!
```

```bash [CLI: sync --dry-run]
$ testlink sync --dry-run

  Syncing Coverage Links
  ──────────────────────
  Running in dry-run mode. No files will be modified.

  Scanning test files for coverage links...

  Changes to apply:
    tests/Unit/UserServiceTest.php
      + linksAndCovers(UserService::class.'::create')

  Dry run complete. Would modify 1 file(s).

    Run without --dry-run to apply changes:
    testlink sync
```

```bash [CLI: pair --dry-run]
$ testlink pair --dry-run

  Pairing Placeholders
  ────────────────────
  Running in dry-run mode. No files will be modified.

  Found Placeholders
    ✓ @user-create  1 production × 2 tests = 2 links

  Production Files
    src/Services/UserService.php
      @user-create → UserServiceTest::test_creates_a_new_user

  Test Files
    tests/Unit/UserServiceTest.php
      @user-create → UserService::create

  Dry run complete. Would modify 2 file(s) with 2 change(s).
```

:::