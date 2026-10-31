---
title: Socket client is tested against a stub TCP server
tags: [testing, socket]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
The socket client's tests run against a stub TCP server rather than a real daemon.

**Why:** The suite needs no companion service, so the stub stands in for the daemon.
