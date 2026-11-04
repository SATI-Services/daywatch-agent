---
title: Client::stats() returns decoded counters, or null on ANY failure
tags: [daemon, socket, stats]
status: verified 2026-11-04 auto
source: [src/Ingest/Client.php]
as_of: 961f8e274 2026-11-04
---
`Client::stats()` sends a STATS frame and reads the daemon's counters reply (agent protocol §4). It returns the decoded counters, or `null` on ANY failure — dead daemon, no reply (pre-STATS daemon or token mismatch), timeout, malformed JSON. It never throws.

**Why:** The `Client` docblock states implementations MUST swallow every failure so a digest never throws into the host app.
