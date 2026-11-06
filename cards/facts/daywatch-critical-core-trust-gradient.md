---
title: Daywatch trust gradient — human-designed core vs. AI-built disposable surface
tags: [trust, critical-core, track]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: 20dd42e0f 2026-11-06
---
**Critical core — human-designed, high test bar, Track B + review:** `src/Records/Envelope.php` + record DTO field shapes (the shared wire mapping), `src/Ingest/SocketClient.php` framing, `src/Daemon/` (frame parser, ingest-dispatcher retry ladder, 503 pause contract), the swallow-all-exceptions guarantee in every sensor/hook, and `composer.json`'s `require` block (the host-compat contract).

**Disposable surface — AI-built for speed, Track A:** docs, README, `resources/boost/` consumer guidelines, test helpers, and TTY-dashboard cosmetics (the display-only paths of `src/Daemon/ConsoleDashboard.php`).

When unsure which side a change is on, treat it as critical core for *test depth* — not for permission.

**Why:** Daywatch's cardinal rule is un-crashability. The critical core enforces it; the disposable surface speeds iteration.

---
