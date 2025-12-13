# Setup IDE Navigation

This guide shows how to configure your IDE to navigate between production code and tests using @see tags.

## How It Works

TestLink uses @see tags in docblocks for IDE navigation:

```php
/**
 * @see \Tests\UserServiceTest::test_creates_user
 */
public function create(array $data): User
```

Most IDEs recognize @see tags and allow Ctrl+Click (or Cmd+Click) to jump to the referenced code.

## PhpStorm Setup

### Enable @see tag navigation

PhpStorm supports @see navigation out of the box:

1. Hold `Ctrl` (Windows/Linux) or `Cmd` (Mac)
2. Click on the class or method reference
3. PhpStorm jumps to that location

### Verify it works

In your production code with @see tags:

```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
public function create(array $data): User
```

`Ctrl+Click` on `test_creates_user` should jump to the test.

### Troubleshooting PhpStorm

**@see links not clickable:**

1. Ensure the class is fully qualified (`\Tests\...`)
2. Run `composer dump-autoload`
3. Invalidate caches: File → Invalidate Caches

**Wrong file opens:**

1. Check the namespace is correct
2. Verify the method name matches exactly

## VS Code Setup

### Install PHP Intelephense

The Intelephense extension provides @see navigation:

1. Open Extensions (`Ctrl+Shift+X`)
2. Search for "PHP Intelephense"
3. Click Install

### Configure Intelephense

Add to `.vscode/settings.json`:

```json
{
  "intelephense.files.associations": ["*.php"],
  "intelephense.environment.documentRoot": "${workspaceFolder}",
  "intelephense.environment.includePaths": [
    "${workspaceFolder}/vendor"
  ]
}
```

### Using navigation

1. Hold `Ctrl` (Windows/Linux) or `Cmd` (Mac)
2. Click on the @see reference
3. VS Code jumps to the target

## Adding @see Tags

### Generate with sync

Run sync to add @see tags automatically:

```bash
./vendor/bin/testlink sync
```

This adds @see tags to test files pointing to production code.

### Add manually

```php
/**
 * Creates a new user.
 *
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 * @see \Tests\Unit\UserServiceTest::test_validates_email
 */
public function create(array $data): User
```

### In tests

```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
{
    // ...
}
```

## @see Tag Format

### Class references

```php
/** @see \App\Services\UserService */
```

### Method references

```php
/** @see \App\Services\UserService::create */
```

### Multiple references

```php
/**
 * @see \App\Services\UserService::create
 * @see \App\Services\UserService::validate
 */
```

## FQCN Requirements

Always use fully qualified class names (FQCN):

```php
// Good - fully qualified
/** @see \Tests\Unit\UserServiceTest::test_creates_user */

// Bad - not navigable in all IDEs
/** @see UserServiceTest::test_creates_user */
```

## Bidirectional Navigation

For full bidirectional navigation:

### Production code

```php
/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
public function create(array $data): User
```

### Test code

```php
/**
 * @see \App\Services\UserService::create
 */
public function test_creates_user(): void
```

Now you can:
- From production → Jump to test
- From test → Jump to production

## Keyboard Shortcuts

| IDE | Navigate to Definition |
|-----|----------------------|
| PhpStorm | `Ctrl+Click` or `Ctrl+B` |
| VS Code | `Ctrl+Click` or `F12` |
| Sublime Text | `Ctrl+Click` (with LSP) |

## Validating @see Tags

Check for invalid @see references:

```bash
./vendor/bin/testlink validate
```

This catches:
- Misspelled class names
- Non-existent methods
- Missing FQCN

## Tips

### Keep @see tags up to date

Run sync after refactoring:

```bash
./vendor/bin/testlink sync --prune
```

### Use with #[TestedBy]

@see tags complement #[TestedBy] attributes:

```php
use TestFlowLabs\TestingAttributes\TestedBy;

/**
 * @see \Tests\Unit\UserServiceTest::test_creates_user
 */
#[TestedBy('Tests\Unit\UserServiceTest', 'test_creates_user')]
public function create(array $data): User
```

Both provide navigation, but:
- @see works in any IDE
- #[TestedBy] enables validation
