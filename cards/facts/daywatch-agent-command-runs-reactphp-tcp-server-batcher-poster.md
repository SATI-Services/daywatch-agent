---
title: daywatch:agent command runs ReactPHP TCP server receiving digests, batches and gzips, POSTs to ingest
tags: [daemon, command, cli]
status: verified 2026-09-09 auto
source: [README.md, CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
`php artisan daywatch:agent` runs a long-lived ReactPHP TCP server that accepts digests over TCP from app instances, batches and gzips them, and POSTs to the configured ingest endpoint. Use `--plain` for non-TTY / log files.

**Why:** Centralized daemon batches and compresses telemetry from all app instances, reducing bandwidth to the ingest.
