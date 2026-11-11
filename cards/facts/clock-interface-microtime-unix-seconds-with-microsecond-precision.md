---
title: Clock.microtime() returns Unix seconds with microsecond precision as float
tags: [time, testing, injection]
status: verified 2026-11-11 auto
source: [src/Support/Clock.php]
as_of: b92d114da 2026-11-10
---
Clock interface defines microtime(): float to return current wall-clock time in Unix seconds with microsecond precision. Injected everywhere durations and timestamps are computed so tests can freeze the clock and assert exact integer-microsecond durations.

**Why:** Enables deterministic testing of time-dependent behavior without mocking system time globally.
