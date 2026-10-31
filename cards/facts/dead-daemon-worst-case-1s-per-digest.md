---
title: Dead-daemon worst case is ~1 s per digest with zero user-visible exceptions
tags: [performance, socket, uncrashable]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: a914f6c40 2026-10-31
---
With the daemon down, a digest costs the host at most ~1 s — 0.5 s connect timeout plus 0.5 s write timeout in `Ingest/SocketClient` — and no exception escapes into the host app.

**Why:** This is the quantified worst case behind the cardinal rule that the package must be un-crashable: the host pays bounded latency, never a failure.
