---
title: Head-sampling rates configurable (default 1.0); errors escape sampled-out traces
tags: [configuration, sampling, telemetry]
status: verified 2026-09-09 auto
source: [README.md]
as_of: 16a6c506a 2026-09-06
---
`DAYWATCH_REQUEST_SAMPLE_RATE` and `DAYWATCH_EXCEPTION_SAMPLE_RATE` (1.0 = keep everything, default 1.0). Errors escape sampled-out traces via re-roll on `report()`.

**Why:** Allows cost control while ensuring errors are never lost to sampling.
