---
title: SocketClient frames each digest as {len}:v1:{hash}:{json}
tags: [daemon, socket, wire-contract]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: da814d4f7 2026-10-31
---
`Ingest/SocketClient.php` frames each digest as `{len}:v1:{hash}:{json}` and sends it over its TCP socket to the daemon.

**Why:** The framing is part of the cross-repo wire contract — it changes only via the `daywatch-payloads` skill against `system/agent-protocol.md`, never package-locally.
