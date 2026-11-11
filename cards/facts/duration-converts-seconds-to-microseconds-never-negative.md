---
title: Duration.us() converts seconds to integer microseconds, clamped ≥ 0
tags: [wire, support]
status: verified 2026-11-11 auto
source: [src/Support/Duration.php]
as_of: 43f0e0487 2026-11-11
---
Duration.us(float $seconds) converts a seconds delta to integer microseconds via `round($seconds * 1_000_000)`, returning 0 when seconds ≤ 0.0.

**Why:** Durations travel on the wire as integer microseconds (agent-faithful, no float drift); this is the single place seconds are converted.
