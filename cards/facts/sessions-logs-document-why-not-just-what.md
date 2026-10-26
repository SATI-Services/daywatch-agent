---
title: Session logs document the why, not just the what
tags: [session-logs, documentation]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: bd1f2a930 2026-10-26
---
Git captures the *what*. The session logs are the only place the *why* survives, so each log must capture: why this approach over the alternative, what it was tested against (env, real round-trip vs fixtures, suite green?), what's deliberately deferred and why, and what's still gated.

A log written as you go is two minutes; reconstructing it from a diff later is a morning and often wrong.

**Why:** A diff shows the change, never the reasoning behind it — the why survives only in the logs.
