---
title: Append only in Daywatch's hot files: Envelope.php, config/daywatch.php, composer.json
tags: [git, merge, hotfile]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: c67a73d16 2026-11-06
---
In a known hot file, **append your entry only** — never reorder, reflow or re-sort the existing entries in the same commit. Reordering turns a clean append into a whole-file conflict and invites a bad resolution.

Daywatch's hot files: `src/Records/Envelope.php` (every record type maps through it), `config/daywatch.php` (the option table), and `composer.json` (the constraints every tooling change wants to touch).

**Why:** The dominant multi-agent failure mode is silent merge loss in files many people touch — a route, an import or a whole feature vanishes with no conflict marker to warn you.
