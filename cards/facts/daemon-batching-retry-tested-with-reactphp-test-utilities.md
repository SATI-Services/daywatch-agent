---
title: Daemon batching and retry are tested with ReactPHP's test utilities
tags: [testing, daemon]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
Daemon batching and retry are exercised with ReactPHP's test utilities.

**Why:** Those paths live on the event loop, so the suite drives them in-process instead of against a live daemon.
