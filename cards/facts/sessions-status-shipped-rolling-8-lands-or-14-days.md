---
title: status ## Shipped is a rolling ticker — ~8 most-recent lands or ~last 14 days, each with evidence
tags: [session-logs, status, shipped]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
`## Shipped` in `status/<author>.md` is a rolling ticker: keep only the ~8 most-recent lands (or ~last 14 days), each with evidence (a sha, PR #, or route on the trunk), and delete older entries.

**Why:** The dated session logs are the permanent archive, so re-listing old ships in the status file is redundant; the rolling window keeps the file under its ~80-line cap.
