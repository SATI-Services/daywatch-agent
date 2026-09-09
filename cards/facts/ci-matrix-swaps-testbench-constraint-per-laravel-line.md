---
title: CI matrix swaps the testbench constraint per Laravel line before composer update
tags: [ci, dependencies, dev-tooling, testbench]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 024e6b908 2026-09-09
---
The CI matrix swaps the testbench constraint per Laravel line (`^9.2` / `^10` / `^11`) before running `composer update`. The `require-dev` tilde pin (testbench `~11.1.0`) therefore governs only local dev installs, not CI.

**Why:** The CI matrix runs the suite on Laravel 11/12/13, and a single tilde-locked testbench pin cannot cover all three lines.
