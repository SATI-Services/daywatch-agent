---
title: SHARED.md holds only cross-cutting state; append, don't rewrite
tags: [session-logs, shared-memory]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: d4813a7e6 2026-10-25
---
SHARED.md holds only what concerns more than one person: shared blockers, prod-wide infra facts, credential rotations, current-priorities board, short recently-shipped ticker. Keep ≤~50 lines. Append, don't rewrite — appends merge cleanly.

**Why:** Multiple authors read-first; append-only eliminates write-merge collisions.
