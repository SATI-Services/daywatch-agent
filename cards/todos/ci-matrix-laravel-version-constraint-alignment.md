---
title: Align composer.json Laravel constraint with CI testability
tags: [ci, composer, owner-decision]
status: open
owner: ryan
since: 2026-09-06
source: [docs/sessions/notes/2026-09-06-kimi-to-ryan-ci-fixes.md, composer.json]
as_of: a5e5084a1 2026-09-12
---
`composer.json` declares `illuminate/support: ^11.0|^12.0|^13.0` but CI tests only Laravel 12/13 (Laravel 11 legs advisory-blocked upstream). Either narrow the `require` constraint to `^12.0|^13.0` or configure `composer policy.advisories` to allow the known advisories. Done = choice made and `composer.json` or policy aligned with CI matrix + docs.
