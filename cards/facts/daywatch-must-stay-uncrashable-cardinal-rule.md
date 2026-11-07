---
title: The package must stay un-crashable — every hook and socket write is wrapped
tags: [invariant, safety, cardinal-rule]
status: verified 2026-11-07 auto
source: [docs/WORKFLOW.md]
as_of: 34b58366e 2026-11-06
---
**The package must stay un-crashable** — every event hook, sensor call, socket write, and encode is wrapped and failures are swallowed; any code path that can throw into a host app is a P0 bug. This is invariant #1 of `docs/WORKFLOW.md`, described there as the cardinal rule in `CLAUDE.md`.

**Why:** The swallow-all-exceptions guarantee in every sensor/hook is classed as critical core (human-designed, high test bar, Track B + review), and any code path that could throw into a host app is named a blast-radius concern. The "hostile host" suite is the automated proof — it plays the adversarial host (dead daemon, wrong token, oversized payloads) and shows the package works with no server at all.
