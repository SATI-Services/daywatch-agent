---
title: Shipped entries in status/<author>.md carry sha, PR, or trunk-route evidence
tags: [session-logs, collaboration, shipped-ticker]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: d4813a7e6 2026-10-25
---
The `## Shipped` ticker in `status/<author>.md` carries evidence: a commit sha, PR #, or route on the trunk. "PR open" is not shipped — it stays in `## In flight` until merged. The `status-reconcile` process skill is the checklist for what counts.

**Why:** "PR open" is not shipped; merged items move out of `## In flight`, so the ticker lists only what actually landed.
