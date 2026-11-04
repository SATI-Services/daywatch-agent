---
title: Scheduler wraps ReactPHP loop timers (LoopScheduler) or is stubbed in tests
tags: [daemon, testing, timing]
status: verified 2026-11-04 auto
source: [src/Daemon/Contracts/Scheduler.php]
as_of: 2c6c843ba 2026-11-03
---
Production Scheduler (LoopScheduler) wraps ReactPHP event loop timers. Tests stub it with a fake that captures and fires callbacks on demand, so retry-ladder and pause timing are asserted deterministically with no real sleeps.

**Why:** Allows testing all retry and pause sequences with millisecond precision, no real delays.
