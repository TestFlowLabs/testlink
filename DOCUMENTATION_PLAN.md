# TestLink Diátaxis Documentation Plan

## Overview

This plan restructures the TestLink documentation according to the Diátaxis framework. Each phase is sized for a single agent to complete in one session.

**Language:** English
**Strategy:** Complete replacement (delete old docs, write new structure)
**Plan file:** Also saved to `DOCUMENTATION_PLAN.md` in project root

## Diátaxis Framework

| Category | Purpose | Content Type |
|----------|---------|--------------|
| **Tutorials** | Learning-oriented | Step-by-step lessons |
| **How-to Guides** | Task-oriented | Problem-solving recipes |
| **Reference** | Information-oriented | Technical documentation |
| **Explanation** | Understanding-oriented | Conceptual discussions |

---

## Phase 1: Directory Structure and VitePress Configuration

**Goal:** Create new Diátaxis folder structure and update VitePress configuration.

**Tasks:**
1. Delete all existing docs content under `docs/`
2. Create new folder structure:
   - `docs/tutorials/` (Tutorials)
   - `docs/how-to/` (How-to Guides)
   - `docs/reference/` (Reference)
   - `docs/explanation/` (Explanation)
3. Update `.vitepress/config.ts` for new structure
4. Update homepage (`index.md`) - redirect to Diátaxis categories

**Output:** New directory structure and updated navigation

**Critical files:**
- `docs/.vitepress/config.ts`
- `docs/index.md`

---

## Phase 2: Tutorials - Basic Tutorials

**Goal:** Create learning-oriented content for beginners.

**Files to create:**
1. `tutorials/index.md` - Tutorials landing page
2. `tutorials/getting-started.md` - Initial setup and basic usage
3. `tutorials/first-bidirectional-link.md` - First #[TestedBy] and linksAndCovers link
4. `tutorials/understanding-reports.md` - Reading and understanding report output

**Reference from current docs:**
- `docs/introduction/quick-start.md` - base content
- `docs/introduction/installation.md` - setup steps

**Output:** 4 tutorial files

---

## Phase 3: Tutorials - TDD Workflow (Detailed)

**Goal:** Create comprehensive tutorial for TDD workflow.

**Files to create:**
1. `tutorials/tdd/index.md` - TDD tutorial series introduction
2. `tutorials/tdd/red-green-refactor.md` - Red-Green-Refactor cycle fundamentals
3. `tutorials/tdd/placeholder-tdd.md` - Fast TDD using placeholders
4. `tutorials/tdd/complete-example.md` - End-to-end TDD example (PriceCalculator style)

**Reference from current docs:**
- `docs/workflow/tdd.md` - expand and split
- Test fixtures for examples

**Output:** 4 TDD tutorial files

---

## Phase 4: Tutorials - BDD Workflow (Detailed)

**Goal:** Create comprehensive tutorial for BDD workflow.

**Files to create:**
1. `tutorials/bdd/index.md` - BDD tutorial series introduction
2. `tutorials/bdd/double-loop.md` - Double-loop TDD/BDD concept
3. `tutorials/bdd/acceptance-to-unit.md` - From acceptance tests to unit tests
4. `tutorials/bdd/placeholder-bdd.md` - Placeholder usage in BDD
5. `tutorials/bdd/complete-example.md` - End-to-end BDD example (ShoppingCart style)

**Reference from current docs:**
- `docs/workflow/bdd.md` - expand and split

**Output:** 5 BDD tutorial files

---

## Phase 5: How-to Guides - Basic Tasks

**Goal:** Create step-by-step guides for common tasks.

**Files to create:**
1. `how-to/index.md` - How-to landing page
2. `how-to/add-links-to-existing-tests.md` - Adding links to existing tests
3. `how-to/add-testedby-to-production.md` - Adding #[TestedBy] to production code
4. `how-to/run-validation-in-ci.md` - Running validation in CI/CD
5. `how-to/fix-validation-errors.md` - Fixing validation errors

**Reference from current docs:**
- `docs/guide/validation.md` - split into tasks
- `docs/best-practices/ci-integration.md` - convert

**Output:** 5 how-to files

---

## Phase 6: How-to Guides - Sync and Placeholder

**Goal:** Create guides for sync and placeholder operations.

**Files to create:**
1. `how-to/sync-links-automatically.md` - Automatic synchronization
2. `how-to/use-dry-run-mode.md` - Using dry-run mode
3. `how-to/resolve-placeholders.md` - Resolving placeholders
4. `how-to/prune-orphan-links.md` - Cleaning up orphan links
5. `how-to/handle-nm-relationships.md` - Managing N:M relationships

**Reference from current docs:**
- `docs/auto-sync/` - convert
- `docs/guide/placeholder-pairing.md` - split

**Output:** 5 how-to files

---

## Phase 7: How-to Guides - Advanced

**Goal:** Create guides for advanced scenarios.

**Files to create:**
1. `how-to/migrate-existing-project.md` - Adding TestLink to existing project
2. `how-to/setup-ide-navigation.md` - IDE @see tag navigation
3. `how-to/organize-tests-with-describe.md` - Test organization with describe blocks
4. `how-to/use-see-tags.md` - Using @see tags
5. `how-to/debug-parsing-issues.md` - Debugging parsing issues

**Reference from current docs:**
- `docs/guide/see-tags.md` - convert
- `docs/guide/troubleshooting.md` - split
- `docs/best-practices/test-organization.md` - convert

**Output:** 5 how-to files

---

## Phase 8: Reference - CLI Commands

**Goal:** Create comprehensive CLI command reference documentation.

**Files to create:**
1. `reference/index.md` - Reference landing page
2. `reference/cli/index.md` - CLI overview
3. `reference/cli/report.md` - testlink report detailed reference
4. `reference/cli/validate.md` - testlink validate detailed reference
5. `reference/cli/sync.md` - testlink sync detailed reference
6. `reference/cli/pair.md` - testlink pair detailed reference

**Reference from current docs:**
- `docs/guide/cli-commands.md` - split and expand

**Output:** 6 reference files

---

## Phase 9: Reference - Attributes and API

**Goal:** Create attribute and API reference documentation.

**Files to create:**
1. `reference/attributes/index.md` - Attributes overview
2. `reference/attributes/testedby.md` - #[TestedBy] detailed reference
3. `reference/attributes/linksandcovers.md` - #[LinksAndCovers] detailed reference
4. `reference/attributes/links.md` - #[Links] detailed reference
5. `reference/pest-methods.md` - Pest method chain reference (linksAndCovers(), links())
6. `reference/configuration.md` - Configuration options

**Reference from current docs:**
- `docs/guide/testedby-attribute.md` - convert
- `docs/guide/covers-method-helper.md` - convert

**Output:** 6 reference files

---

## Phase 10: Explanation - Conceptual Topics

**Goal:** Create content explaining concepts behind TestLink.

**Files to create:**
1. `explanation/index.md` - Explanation landing page
2. `explanation/bidirectional-linking.md` - What is bidirectional linking and why it matters
3. `explanation/two-package-architecture.md` - Why two-package architecture is needed
4. `explanation/links-vs-linksandcovers.md` - Difference between Links and LinksAndCovers
5. `explanation/placeholder-strategy.md` - Placeholder strategy and use cases
6. `explanation/test-traceability.md` - Test traceability concept and benefits

**Reference from current docs:**
- `docs/introduction/what-is-testlink.md` - expand
- `docs/introduction/installation.md` - architecture section
- `docs/guide/test-coverage-links.md` - convert

**Output:** 6 explanation files

---

## Phase 11: Explanation - TDD/BDD Deep Dive

**Goal:** In-depth explanation of TDD and BDD concepts in TestLink context.

**Files to create:**
1. `explanation/tdd/index.md` - TDD conceptual explanation intro
2. `explanation/tdd/why-tdd-with-links.md` - Benefits of using links in TDD
3. `explanation/tdd/when-to-add-links.md` - When to add links in TDD cycle
4. `explanation/bdd/index.md` - BDD conceptual explanation intro
5. `explanation/bdd/acceptance-vs-unit.md` - Difference between acceptance and unit test links
6. `explanation/bdd/placeholder-in-bdd.md` - Placeholder usage strategy in BDD

**Source:** New content + conceptual parts from existing workflow docs

**Output:** 6 explanation files

---

## Phase 12: Cleanup

**Goal:** Remove old files and finalize structure.

**Tasks:**
1. Remove all old directories and files:
   - `docs/guide/`
   - `docs/workflow/`
   - `docs/best-practices/`
   - `docs/auto-sync/`
   - `docs/introduction/`
2. Final VitePress config adjustments
3. Verify no broken internal links

**Output:** Clean and consistent documentation structure

---

## Phase 13: Final Review and Improvements

**Goal:** Review all documentation and make final adjustments.

**Tasks:**
1. Verify all internal links work
2. Check code examples consistency
3. Ensure Pest and PHPUnit examples exist everywhere
4. Test navigation flow
5. Finalize homepage and index pages
6. Update README.md (redirect to docs)

**Output:** Production-ready documentation

---

## Summary: Phase List

| Phase | Description | Output Count |
|-------|-------------|--------------|
| 1 | Directory Structure and VitePress | ~3 files |
| 2 | Tutorials - Basic | 4 files |
| 3 | Tutorials - TDD | 4 files |
| 4 | Tutorials - BDD | 5 files |
| 5 | How-to - Basic | 5 files |
| 6 | How-to - Sync/Placeholder | 5 files |
| 7 | How-to - Advanced | 5 files |
| 8 | Reference - CLI | 6 files |
| 9 | Reference - Attributes | 6 files |
| 10 | Explanation - Conceptual | 6 files |
| 11 | Explanation - TDD/BDD | 6 files |
| 12 | Cleanup | - |
| 13 | Final Review | - |

**Total:** ~55 new/updated files, 13 phases

---

## Notes

- Each phase can be completed independently
- TDD/BDD sections are covered in 4 phases (Phases 3, 4, 11, and partially 2)
- Agent can mark each phase as "completed" when done
- Phases should be applied sequentially (especially Phase 1 first)
- Phase 12 handles cleanup after all content is created
