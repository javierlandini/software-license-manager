---
name: Task
about: A self-contained unit of work (feature, fix, chore) for a person or an agent.
title: "[X-00] Short imperative summary"
labels: []
---

<!--
Labels: see the "Labels" section of CLAUDE.md. Pick one status label when it applies
(agent-ready, needs-approval, needs-decision), one size:*, one day:* (or extra) and one area:*.
-->

## Context

<!-- Why this is needed and what already exists. Link related issues and files. -->

**Depends on:** <!-- #issue, or "nothing" -->

## What

<!-- The change to make, concretely: files, routes, classes, behaviour. -->

## Acceptance criteria

- [ ] <!-- Observable, testable outcome. -->

## How to verify

<!-- Tests to run or write, commands, manual steps. -->

## Approval

<!--
Only for issues labelled needs-approval; delete this section otherwise.
State exactly which protected area (see "Sensitive areas" in CLAUDE.md) this issue authorizes
changing, e.g.: "This issue authorizes modifying `.github/workflows/lint.yml`." The scope above is
the only change authorized; anything beyond it needs a new approval.
-->

## Out of scope

Anything not listed above. If something else looks necessary, open a separate issue instead of expanding this one.

## Working agreement

Follow `CLAUDE.md`: branch `feature/<kebab-case>`, PR with `Closes #<this issue>`, English for code, docs and commits, Pest tests (lowercase `test('...')`), run `composer check` before opening the PR.
