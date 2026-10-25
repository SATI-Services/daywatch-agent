---
title: Release 1.3.0 commit 2e5e303 π-corrects exception executions' combined rate
tags: [release, sampling, exceptions]
status: verified 2026-10-25 auto
source: [docs/sessions/ryan/2026-10-12-release-1.3.0.md]
as_of: 915df1abb 2026-10-25
---
`2e5e303` in release `1.3.0` π-corrects executions with exceptions: they stamp the COMBINED rate
`r + (1−r)·sampling.exceptions` and are kept by EITHER roll.

**Why:** Won-roll stamping over-weights error counts ~2×; the combined rate keeps exception-bearing traces
weighted correctly when both the head rate and the exception rate apply.
