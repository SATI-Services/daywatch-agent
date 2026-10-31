---
title: Release 1.0.2 — VERSION const in src/Daywatch.php is the single version source
tags: [release, versioning]
status: verified 2026-10-31 auto
source: [docs/sessions/ryan/2026-10-06-release-1.0.2.md]
as_of: f03ba15a2 2026-10-30
---
`src/Daywatch.php` VERSION const is the single version source; bumped `1.0.1` → `1.0.2`. Other VERSION references (AgentServiceProvider, Envelope, ConnectionHandler, Payload, ExceptionSensor) read from it; no other code changes needed.

**Why:** Centralized version management — no duplication across service provider, envelope, handlers, payloads, or sensors.

---
