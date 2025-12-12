---
title: "@see Tag Syntax Support"
description: "ADR: Exploring @see PHPDoc tag syntax as an alternative to PHP 8 attributes"
---

# ADR: @see Tag Syntax Support

| Field | Value |
|-------|-------|
| **Status** | Implemented |
| **Date** | 2025-12-12 |
| **Decision** | Implemented @see tag support integrated into existing commands |

## Summary

Explored adding `@see` PHPDoc tag syntax as an alternative to PHP 8 attributes for test-production linking in TestLink.

## Background

Existing pattern (pre-TestLink, manual approach):

::: code-group
```php [Test File]
/**
 * @see \App\Services\UserService::create
 */
#[Test]
public function it_creates_user(): void
```

```php [Production File]
/**
 * @see \Tests\Unit\UserServiceTest::it_creates_user()
 * @see \Tests\Unit\UserServiceTest::it_validates_email()
 */
public function create(string $email): User
```
:::

### Benefits of @see Approach

1. **IDE Support** - PHPStorm makes @see tags clickable (Ctrl+Click navigation)
2. **Standard PHPDoc** - Well-known, documented tag
3. **No Dependencies** - Works without TestLink installed
4. **Familiar Syntax** - FQCN::method format already used elsewhere

## Research Findings

### 1. Parsing Infrastructure

- TestLink uses `nikic/php-parser` for AST parsing
- Already extracts DocComments via `getDocComment()->getText()`
- **@see parsing is technically easy** - infrastructure exists

### 2. IDE Support Comparison

| Feature | @see Tags | PHP 8 Attributes |
|---------|-----------|------------------|
| Class navigation | :white_check_mark: Clickable | :white_check_mark: Clickable |
| **Method navigation** | :white_check_mark: Clickable | :x: String only |
| Refactoring | Manual | Automatic (class only) |
| TestLink validation | :x: None | :white_check_mark: Full |
| Pest support | :x: No | :white_check_mark: Yes |
| **Production dependency** | :white_check_mark: None needed | :x: Requires test-attributes |

### 3. The Navigation Gap

```php
// @see - FULL NAVIGATION
/** @see \Tests\UserServiceTest::testCreate */
//        ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ Ctrl+Click → goes to METHOD

// #[TestedBy] - PARTIAL NAVIGATION
#[TestedBy(UserServiceTest::class, 'testCreate')]
//         ^^^^^^^^^^^^^^^^^^^^^ Ctrl+Click → goes to CLASS
//                                 ^^^^^^^^^^^^ String - NOT clickable!
```

PHPStorm parses `@see \Namespace\Class::method` as a **complete reference**.
PHPStorm parses `#[Attr(Class::class, 'method')]` as **class constant + string**.

::: info Source
No JetBrains attribute exists to make method name strings navigable.
See: [JetBrains/phpstorm-attributes](https://github.com/JetBrains/phpstorm-attributes)
:::

### 4. Package Architecture Impact

::: code-group
```text [Current Architecture]
Production Code
├── composer.json:
│   "require": {
│     "testflowlabs/test-attributes": "^1.0"  ← REQUIRED
│   }
│
└── Why? #[TestedBy] must exist when PHP loads production classes
```

```text [With @see Support]
Production Code
├── composer.json:
│   "require": { }                    ← NO TEST PACKAGES
│   "require-dev": {
│     "testflowlabs/testlink": "^1.0" ← Dev only
│   }
│
└── Why? @see is just a comment, no runtime need
```
:::

**Key Insight:** @see allows ZERO production dependencies for test traceability.

## Pest Compatibility

### The Challenge

Pest tests are function calls, not class methods:

```php
// Pest internal identifier format:
"Tests\ExampleTest::creates user"  // Description as pseudo-method

// @see syntax requires valid PHP identifiers:
@see \Tests\ExampleTest::creates user  // INVALID - spaces not allowed
```

### Solution Found!

PHPDoc **CAN** be attached to `test()` function calls:

```php
/**
 * @see \App\Services\UserService::create   ← TestLink CAN parse this!
 */
test('creates user', function() {
    // ...
});
```

**How it works:**
- nikic/php-parser attaches docblocks to the NEXT statement
- `test()` is a `FuncCall` node which HAS `getDocComment()` method
- `PestTestParser` already works with these nodes

**Limitation:** PHPStorm won't navigate (function call ≠ definition)

### Feasibility Matrix

```
┌──────────────────────────────────────────────────────────────┐
│  @see-Based TestLink - Feasibility                           │
├──────────────────────┬──────────┬────────────────────────────┤
│                      │ PHPUnit  │ Pest                       │
├──────────────────────┼──────────┼────────────────────────────┤
│ Prod → Test (@see)   │ ✅ Yes   │ ⚠️ Non-standard path       │
│ Test → Prod (@see)   │ ✅ Yes   │ ✅ Yes (above test())      │
│ IDE Navigation       │ ✅ Yes   │ ❌ No (function call)      │
│ TestLink Parsing     │ ✅ Yes   │ ✅ Yes                     │
└──────────────────────┴──────────┴────────────────────────────┘
```

## Possible Directions

### Direction 1: Accept the Trade-off (Current)

- Attributes: TestLink tooling :white_check_mark:, full method navigation :x:
- @see: Full method navigation :white_check_mark:, TestLink tooling :x:
- Users can use BOTH side by side

### Direction 2: IDE Plugin

- Create PHPStorm plugin for attribute navigation
- Works for both PHPUnit and Pest
- Significant development effort

### Direction 3: Hybrid Approach

- Auto-generate @see tags from attributes
- `testlink sync --with-see-tags`
- Best of both worlds

## Decision

**Decision:** Implemented @see tag support, integrated into existing commands.

### Implementation

@see tag support was integrated into the existing CLI commands rather than as a separate command:

- **`testlink sync`**: Automatically generates @see tags in production docblocks
- **`testlink validate`**: Detects orphan @see tags pointing to invalid targets
- **`testlink report`**: Displays @see tags in the coverage report
- **`testlink sync --prune --force`**: Removes orphan @see tags

### Rationale

1. @see tags provide full IDE method navigation (vs class-only for attributes)
2. @see eliminates production dependency on test-attributes package
3. Integration into existing commands maintains consistent CLI experience
4. PHPUnit projects benefit most; Pest has known limitations (see above)

### Documentation

See [@see Tags Guide](/guide/see-tags) for usage documentation.

## Key Findings Summary

| Finding | Impact |
|---------|--------|
| @see CAN work for both PHPUnit and Pest | TestLink parsing possible |
| Pest @see navigation requires IDE plugin | PHPStorm limitation |
| @see eliminates production dependency | Architectural benefit |
| nikic/php-parser supports this | `$node->getDocComment()` on FuncCall |
| Implementation is feasible | Needs architectural decision first |
