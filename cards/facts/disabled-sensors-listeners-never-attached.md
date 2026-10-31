---
title: A disabled sensor's listeners are never attached — the enabled gate runs at attach time
tags: [sensors, config]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: a914f6c40 2026-10-31
---
`SensorManager` is the per-sensor `enabled` gate (`sensors.{class}.enabled`, keyed by class in `config/daywatch.php`): a disabled sensor's event listeners are never attached to the framework's dispatcher.

**Why:** Gating at attach time — not filtering at fire time — means a disabled sensor receives no framework events at all.
