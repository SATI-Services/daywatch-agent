---
title: '"PR open" is not shipped — it stays in ## In flight until merged'
tags: [session-logs, status]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
An open PR does not count as shipped in `status/<author>.md`: the item stays in `## In flight` until it merges. The `status-reconcile` process skill is the checklist for what counts.

**Why:** `## Shipped` lines carry landed evidence (a sha, PR #, or route on the trunk); an open PR has none yet.
