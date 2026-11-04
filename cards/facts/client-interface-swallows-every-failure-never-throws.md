---
title: Client implementations swallow every failure, returning false or null
tags: [daemon, socket, contract]
status: verified 2026-11-04 auto
source: [src/Ingest/Client.php]
as_of: f7f795e35 2026-11-04
---
The `Client` interface contract: `send()` and `ping()` MUST swallow every failure and return false; `stats()` MUST return the decoded counters, or null on ANY failure (dead daemon, no reply, timeout, malformed JSON). A digest can never throw into the host app.

**Why:** An unguarded exception propagates into the host app.
