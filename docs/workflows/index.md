# Workflows

TestLink integrates seamlessly with Test-Driven Development (TDD) and Behavior-Driven Development (BDD) workflows.

## TDD Workflow

Test-Driven Development with bidirectional linking.

- [Introduction](./tdd/) - TDD fundamentals with TestLink
- [Red-Green-Refactor](./tdd/red-green-refactor) - The classic TDD cycle
- [Placeholders](./tdd/placeholders) - Using @placeholder markers
- [Complete Example](./tdd/complete-example) - Full TDD walkthrough
- [Why TDD with Links?](./tdd/why-tdd-with-links) - Benefits of linking during TDD
- [When to Add Links](./tdd/when-to-add-links) - Timing your links

## BDD Workflow

Behavior-Driven Development with acceptance and unit tests.

- [Introduction](./bdd/) - BDD fundamentals with TestLink
- [Double-Loop](./bdd/double-loop) - Outside-in development
- [Acceptance to Unit](./bdd/acceptance-to-unit) - From feature to implementation
- [Placeholders](./bdd/placeholders) - Using @placeholder in BDD
- [Complete Example](./bdd/complete-example) - Full BDD walkthrough
- [Acceptance vs Unit Links](./bdd/acceptance-vs-unit) - When to use links() vs linksAndCovers()
- [Placeholder Concepts](./bdd/placeholder-concepts) - Deep dive into BDD placeholders

## Sync vs Pair: Which Command to Use?

TestLink provides two commands for managing links. Here's when to use each:

| Command | Use When | What It Does |
|---------|----------|--------------|
| `testlink sync` | Links already use real class references | Synchronizes `#[TestedBy]` ↔ `linksAndCovers()` bidirectionally |
| `testlink pair` | Links use `@placeholder` markers | Resolves placeholders to real class references |

### Typical Workflow

1. **During development**: Use `@placeholder` markers for fast iteration
2. **Before commit**: Run `testlink pair` to resolve placeholders
3. **After refactoring**: Run `testlink sync` to keep links synchronized

### Decision Matrix

```
Start
  │
  ├─ Are you using @placeholder markers?
  │   ├─ Yes → Use `testlink pair`
  │   └─ No  → Use `testlink sync`
  │
  └─ Do links already exist with real references?
      ├─ Yes → Use `testlink sync` to propagate changes
      └─ No  → Add links manually or use placeholders
```

See [How-to: Sync Links Automatically](/how-to/sync-links-automatically) and [How-to: Resolve Placeholders](/how-to/resolve-placeholders) for detailed guides.
