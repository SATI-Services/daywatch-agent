---
title: To exercise daemon against real ingest, symlink package into host app via composer path repository
tags: [dev, testing, ingest]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: 41d44b355 2026-10-31
---
To exercise the daemon against a real ingest, symlink the package into a host app via a composer path repository and point `DAYWATCH_BASE_URL` at a running Daywatch stack — useful, but never required for the suite.

**Why:** The self-contained suite is fast and portable. Real-ingest testing is optional and requires a full stack to be running.
