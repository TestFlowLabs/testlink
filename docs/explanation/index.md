# Explanation

Understanding the concepts behind TestLink.

## Overview

This section explains the **why** behind TestLink's design decisions. While tutorials teach you how to use TestLink and how-to guides solve specific problems, these explanations help you understand the deeper concepts.

## Topics

### [Bidirectional Linking](./bidirectional-linking)

What is bidirectional linking and why it matters for test maintainability. Learn how connecting tests to production code (and vice versa) creates a navigable, maintainable test suite.

### [Two-Package Architecture](./two-package-architecture)

Why TestLink is split into two packages (`test-attributes` and `testlink`) and how this architecture ensures your production code works in all environments.

### [Links vs LinksAndCovers](./links-vs-linksandcovers)

Understanding the difference between `#[Links]` and `#[LinksAndCovers]` attributes, and when to use each one for optimal test organization.

### [Placeholder Strategy](./placeholder-strategy)

The placeholder system explained: why temporary markers exist, how they enable rapid TDD/BDD development, and when to use them.

### [Test Traceability](./test-traceability)

What test traceability means, why it matters for software quality, and how TestLink implements it through attributes and method chains.

## Why Understanding Matters

Understanding these concepts helps you:

- **Make better decisions** about how to structure test links
- **Debug issues** when validation fails
- **Optimize workflows** for your team's needs
- **Explain to others** why bidirectional linking improves maintainability

## Related Sections

- [Tutorials](/tutorials/) - Learn TestLink step-by-step
- [How-to Guides](/how-to/) - Solve specific problems
- [Reference](/reference/) - Technical specifications
