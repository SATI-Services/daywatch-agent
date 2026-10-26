---
title: Update status/<you>.md and the commit subject in the same push as the work
tags: [session-logs, status, git]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
Keep `status/<you>.md` current in the same push as the work, with a meaningful commit subject — those two are what let others (and the briefing hook) reconcile what you're doing without asking. Update both when you push, not later.

**Why:** A terse subject (`push`, `wip`) plus a stale status file is how two people end up building the same thing.
