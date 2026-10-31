---
title: Agent catches EADDRINUSE and reports listen address, rival process, and remedies
tags: [daemon, networking, diagnostics]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
EADDRINUSE is caught at boot and reported as "another daywatch:agent is probably already running", with the `daywatch:status` / `lsof -nP -iTCP:{port} -sTCP:LISTEN` / `--listen=` next steps, instead of leaking the raw ReactPHP socket message.

**Why:** A raw socket error is opaque to an operator. A named diagnosis ("another daemon is running") with exact remedies is actionable.
