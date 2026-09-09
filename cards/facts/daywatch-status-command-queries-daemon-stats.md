---
title: daywatch:status requests STATS and prints counters or --json; exits 1 when daemon unreachable
tags: [daemon, command, monitoring, cli]
status: verified 2026-09-09 auto
source: [README.md, CLAUDE.md]
as_of: dbb7df637 2026-09-09
---
`php artisan daywatch:status` requests the daemon's STATS payload (token-gated) and receives an ack plus a `{len}:{json}` reply containing the counters. It presents the counters as a human table by default or machine-readable JSON with `--json`. The command has a PING fallback and, when the daemon is unreachable, prints a clear "daemon unreachable" message and exits with code 1 (never raising an exception).

**Why:** Operators and scripts need a machine-friendly and human-friendly way to verify the daemon is alive and moving records; the exit code supports automation and the JSON flag supports programmatic checks.
