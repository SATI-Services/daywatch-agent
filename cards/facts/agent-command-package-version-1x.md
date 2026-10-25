---
title: Agent sends User-Agent 'DaywatchAgent/1.x' — the version is a hardcoded literal
tags: [daemon, agent]
status: verified 2026-10-25 auto
source: [src/Console/AgentCommand.php]
as_of: f7896fb80 2026-10-24
---
`packageVersion()` returns the static string `'1.x'`. Both `IngestDispatcher` and `AuthProbe` build their User-Agent as `'DaywatchAgent/'.$this->packageVersion()`, so the daemon always identifies itself as `DaywatchAgent/1.x`.

**Why:** The User-Agent carries no build version, so ingest cannot tell releases apart from it.
