---
title: LoopScheduler implements Scheduler using ReactPHP event loop timers
tags: [daemon, scheduling]
status: verified 2026-11-03 auto
source: [src/Daemon/LoopScheduler.php]
as_of: 9cfb6374a 2026-11-03
---
`Daywatch\Agent\Daemon\LoopScheduler` is a final class implementing the `Scheduler` contract. It is constructed with a `React\EventLoop\LoopInterface`, and `after(float $seconds, callable $callback)` schedules the callback with `$loop->addTimer()`.

**Why:** Source docblock: "Production {@see Scheduler} backed by the ReactPHP event loop's timers."
