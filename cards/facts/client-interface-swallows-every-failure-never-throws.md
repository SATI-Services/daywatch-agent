---
title: Client implementations swallow every failure, returning false or null
tags: [daemon, socket, contract]
status: verified 2026-11-04 auto
source: [src/Ingest/Client.php]
as_of: 961f8e274 2026-11-04
---
The `Client` interface contract: `send()` and `ping()` MUST swallow every failure and return false — they return true **only** on a `2:OK` ack. `stats()` MUST return the decoded counters, or null on ANY failure (dead daemon, no reply — pre-STATS daemon or token mismatch — timeout, malformed JSON). A digest can never throw into the host app.

**Why:** An unguarded exception would propagate into the host app; the docblock makes the swallow-and-return-false shape the implementer's obligation, not an accident.
