---
title: ContextPropagation.read() validates sample_rate as numeric, > 0, and ≤ 1
tags: [queue, propagation, validation]
status: verified 2026-11-11 auto
source: [src/Support/ContextPropagation.php]
as_of: b92d114da 2026-11-10
---
ContextPropagation.read() coerces sample_rate to float only if it is numeric AND > 0 AND ≤ 1.0. Out-of-range or non-numeric values become null.

**Why:** Ensures sample_rate is always a valid probability in (0,1] or null; invalid values are ignored, not propagated.
