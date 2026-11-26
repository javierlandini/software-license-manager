Closes #

## Summary

<!-- What changed and why, in a few lines. -->

## Decisions

<!--
Decisions taken while building (required for needs-decision issues), and anything deferred
because it needs approval (see "Sensitive areas" in CLAUDE.md). Write "None" if empty.
-->

## How it was verified

<!-- Tests added or run, manual checks. -->

## Checklist

- [ ] Branch is `feature/<kebab-case>`, based on the latest `main`; this PR targets `main` and is merged with a merge commit.
- [ ] Commits are imperative, one sentence ending with a period.
- [ ] `composer check` passes locally (Pint `--test` + Larastan + tests).
- [ ] `npm run build` passes locally if frontend files or dependencies changed (CI does not run it).
- [ ] Integration tests pass (`composer test:integration`) if DB/Redis behaviour changed.
- [ ] No changes to protected areas (migrations, FK policies, `License.key`, amounts/currency, dependencies, `compose.yaml`, `docker/`, `.github/workflows/`) beyond what the issue explicitly authorizes.
- [ ] If this PR answers an entry under "Open questions" in `CLAUDE.md`, that entry is removed and the decision is recorded where it belongs.
- [ ] `CLAUDE.md` is updated if conventions or commands changed.

<!--
`main` is protected by the owner: merging requires green CI (laravel-tests, integration-tests,
pint, larastan).
-->
