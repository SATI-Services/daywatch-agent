---
title: Session logs capture the why, not just the what git records
tags: [session-logs, archive]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: bd1f2a930 2026-10-26
---
Git captures the *what*. The dated logs are the only place the *why* survives. A log must record: why this approach over the alternative, what it was tested against (env, real round-trip vs fixtures, suite green?), what's deliberately deferred and why, and what's still gated. A log written as you go takes two minutes; reconstructing it from a diff later is a morning and often wrong.

**Why:** Nothing else in the repo preserves the reasoning; a reconstructed log is expensive and often wrong.
