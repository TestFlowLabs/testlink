# Pruning Orphans

Pruning removes test links that no longer have matching `#[TestedBy]` attributes. This happens when:

- A `#[TestedBy]` attribute is removed from production code
- A production method is deleted
- A production method is renamed

## What Are Orphans?

An orphaned link references a method that:
1. Doesn't exist anymore, OR
2. No longer has a corresponding `#[TestedBy]` attribute

::: code-group

```php [Pest - Orphaned Link]
// This linksAndCovers() is orphaned if UserService::oldMethod
// no longer exists or has no #[TestedBy] attribute
test('some test', function () {
    // ...
})->linksAndCovers(UserService::class.'::oldMethod');  // Orphan!
```

```php [PHPUnit - Orphaned Link]
// This #[LinksAndCovers] is orphaned if UserService::oldMethod
// no longer exists or has no #[TestedBy] attribute
#[LinksAndCovers(UserService::class, 'oldMethod')]  // Orphan!
public function test_some_test(): void
{
    // ...
}
```

:::

## Detecting Orphans

Run validation to see orphaned links:

```bash
testlink validate
```

Output:

```
Validation Results:
  ✗ 2 synchronization issues found

Issues:
  1. Orphaned link in: tests/Unit/UserServiceTest.php
     Method: UserService::deletedMethod
     No matching #[TestedBy] attribute found

  2. Orphaned link in: tests/Unit/OldTest.php
     Method: LegacyService::removed
     No matching #[TestedBy] attribute found
```

## Pruning Command

### Safety Requirement

Pruning requires the `--force` flag as a safety measure:

```bash
# This will fail with an error
testlink sync --prune

# This works
testlink sync --prune --force
```

Error message without `--force`:

```
Error: The --prune option requires --force to confirm deletion.

This is a safety measure to prevent accidental removal of links.

Usage: testlink sync --prune --force
```

### Running Prune

```bash
testlink sync --prune --force
```

Output:

```
Scanning production code for #[TestedBy] attributes...

✓ Sync complete!

  0 link(s) added
  2 orphaned link(s) removed

Modified files:
  - tests/Unit/UserServiceTest.php (1 link pruned)
  - tests/Unit/OldTest.php (1 link pruned)
```

## Preview with Dry Run

Always preview before pruning:

```bash
testlink sync --prune --force --dry-run
```

Output:

```
Scanning production code for #[TestedBy] attributes...

Orphaned links to remove:

  tests/Unit/UserServiceTest.php
  └── UserService::deletedMethod
      Line: 45

  tests/Unit/OldTest.php
  └── LegacyService::removed
      Line: 12

Run without --dry-run to apply changes.
```

## How Pruning Works

1. **Build valid registry** - Collects all `#[TestedBy]` attributes
2. **Scan test files** - Finds all link calls/attributes
3. **Compare** - Identifies links not in the valid registry
4. **Remove** - Deletes orphaned links from test files

### Code Removal

::: code-group

```php [Pest - Before]
test('some test', function () {
    // ...
})->linksAndCovers(UserService::class.'::deletedMethod')
  ->linksAndCovers(UserService::class.'::validMethod');
```

```php [Pest - After]
test('some test', function () {
    // ...
})->linksAndCovers(UserService::class.'::validMethod');
```

```php [PHPUnit - Before]
#[LinksAndCovers(UserService::class, 'deletedMethod')]
#[LinksAndCovers(UserService::class, 'validMethod')]
public function test_some_test(): void
{
    // ...
}
```

```php [PHPUnit - After]
#[LinksAndCovers(UserService::class, 'validMethod')]
public function test_some_test(): void
{
    // ...
}
```

:::

## Sync + Prune Together

You can add new links and prune old ones in one command:

```bash
testlink sync --prune --force
```

This:
1. Adds missing links
2. Removes orphaned links

## Best Practices

### 1. Always Use Dry Run First

```bash
testlink sync --prune --force --dry-run
```

### 2. Review Carefully

Orphaned links might indicate:
- A bug (method was accidentally removed)
- Intentional removal (safe to prune)
- Renamed method (update instead of prune)

### 3. Use Version Control

Have a clean git state before pruning:

```bash
git status  # Should be clean
testlink sync --prune --force
git diff    # Review changes
git commit -m "Prune orphaned coverage links"
```

### 4. Don't Prune Blindly

If you see many orphans, investigate:
- Was there a major refactoring?
- Were `#[TestedBy]` attributes accidentally removed?
- Is the classmap up to date? (`composer dump-autoload`)

## Common Scenarios

### After Renaming a Method

1. Update `#[TestedBy]` attributes
2. Run sync to add new links
3. Run prune to remove old links

```bash
testlink sync --prune --force
```

### After Deleting a Method

1. Run prune to remove orphaned links
2. Consider if the test should also be deleted

```bash
testlink sync --prune --force
```

### After Major Refactoring

1. Run dry-run to assess impact
2. Review changes carefully
3. Apply if appropriate

```bash
testlink sync --prune --force --dry-run
# Review output
testlink sync --prune --force
```
