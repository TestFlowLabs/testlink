# IDE Navigation Reference

TestLink's core purpose is enabling **Cmd+Click navigation** between tests and production code. This page covers IDE-specific details and troubleshooting.

## How Navigation Works

TestLink uses `@see` tags that all major PHP IDEs recognize:

```php
class UserService
{
    /**
     * @see \Tests\UserServiceTest::test_creates_user    ← Cmd+Click
     * @see \Tests\UserServiceTest::test_validates_email ← Cmd+Click
     */
    public function create(array $data): User
    {
        // Click any @see tag to jump to that test
    }
}
```

The same works from tests to production:

```php
/**
 * @see \App\Services\UserService::create   ← Cmd+Click
 */
public function test_creates_user(): void
{
    // Click to jump to the production method
}
```

## PhpStorm

PhpStorm supports `@see` navigation out of the box. No configuration needed.

### Navigate

1. Hold `Cmd` (Mac) or `Ctrl` (Windows/Linux)
2. Click on the class or method reference in the `@see` tag
3. PhpStorm jumps to that location

### Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Navigate to definition | `Cmd+Click` or `Cmd+B` |
| Find usages | `Cmd+Shift+F7` |
| Navigate back | `Cmd+[` |

### Troubleshooting

**@see links not clickable:**
1. Ensure the class is fully qualified (`\Tests\...` with leading backslash)
2. Run `composer dump-autoload` to update autoloading
3. Invalidate caches: File → Invalidate Caches → Invalidate and Restart

**Wrong file opens:**
1. Check the namespace matches the file location
2. Verify the method name is spelled correctly

## VS Code

VS Code requires the Intelephense extension for `@see` navigation.

### Setup

1. Open Extensions (`Cmd+Shift+X`)
2. Search for "PHP Intelephense"
3. Click Install

Optional configuration in `.vscode/settings.json`:

```json
{
  "intelephense.files.associations": ["*.php"],
  "intelephense.environment.includePaths": [
    "${workspaceFolder}/vendor"
  ]
}
```

### Navigate

1. Hold `Cmd` (Mac) or `Ctrl` (Windows/Linux)
2. Click on the `@see` reference
3. VS Code jumps to the target

### Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Navigate to definition | `Cmd+Click` or `F12` |
| Navigate back | `Cmd+-` |

## Other IDEs

| IDE | @see Support |
|-----|--------------|
| Sublime Text | With LSP-intelephense plugin |
| Neovim | With nvim-lspconfig + intelephense |
| Eclipse PDT | Built-in |

## Generating @see Tags

TestLink's `sync` command generates `@see` tags automatically:

```bash
./vendor/bin/testlink sync
```

This scans your test declarations and adds corresponding `@see` tags to both sides, making your code navigable without manual work.

## @see Tag Format

### Fully Qualified Names Required

Always use fully qualified class names (FQCN) with a leading backslash:

```php
// Good - navigable in all IDEs
/** @see \Tests\Unit\UserServiceTest::test_creates_user */

// Bad - may not work in some IDEs
/** @see UserServiceTest::test_creates_user */
```

### Class References

```php
/** @see \App\Services\UserService */
```

### Method References

```php
/** @see \App\Services\UserService::create */
```

### Multiple References

```php
/**
 * @see \Tests\UserServiceTest::test_creates_user
 * @see \Tests\UserServiceTest::test_validates_email
 * @see \Tests\UserFlowTest::test_registration_flow
 */
public function create(array $data): User
```

## Keeping Links Accurate

Navigation only works if links point to real code. Run validation to catch broken links:

```bash
./vendor/bin/testlink validate
```

This catches:
- Misspelled class/method names
- References to deleted tests
- Missing FQCN prefixes

Run validation in CI/CD to ensure your navigation links stay accurate as code evolves.

## See Also

- [Bidirectional Linking](/explanation/bidirectional-linking) - How the linking system works
- [Sync Command](/reference/cli/sync) - Auto-generate @see tags
- [Validation in CI](/how-to/run-validation-in-ci) - Keep links accurate
