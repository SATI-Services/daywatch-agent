---
title: SensorManager guards each sensor attach and each fired-listener body independently
tags: [sensors, cardinal-rule]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
Every sensor attach runs through `safely()`, and every listener body registered via `on()` runs inside `try { … } catch (Throwable) {}`.

If one attach fails, the remaining attaches still run; the failure is logged as `[daywatch] sensor wiring failed: <message>` (only when the `log` binding is bound) and never rethrown.

If a listener throws when the event fires, it is swallowed silently — the host app is never affected.

**Why:** The cardinal rule — the package must be uncrashable.
