---
title: Dated session logs are the never-trimmed archive, updated as you go
tags: [session-logs, archive]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: bd1f2a930 2026-10-26
---
Dated logs (`docs/sessions/<author>/YYYY-MM-DD-*.md`) are the never-trimmed archive: update them *as you go*, not at the end. The `## Shipped` ticker in `status/<author>.md` is a rolling ticker, pruned to the ~8 most-recent lands (or ~last 14 days), each with evidence; older entries are deleted because the dated logs are the permanent archive, so re-listing there is redundant.

**Why:** A log written as you go takes two minutes; reconstructing it from a diff later takes a morning and is often wrong.
