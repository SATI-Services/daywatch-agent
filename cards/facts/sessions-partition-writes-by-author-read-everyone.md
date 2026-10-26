---
title: docs/sessions/ partitions writes by author; everyone reads all logs
tags: [session-logs, shared-memory, collaboration]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
`docs/sessions/` is designed to avoid write-collisions: writes are partitioned by author, and everyone reads all logs. No two authors write the same `status/<author>.md`, so cross-author conflict is structurally impossible; if your own status file conflicts, two of *your* sessions raced.

**Why:** Multiple people each run multiple agents concurrently against the same shared memory; partitioning writes by author is what lets them work without merge collisions.
