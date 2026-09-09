---
title: Head sampling decided once per execution; errors escape sampled-out traces
tags: [sampling, protocol, telemetry]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
Head sampling is decided once per execution; sensors always buffer. The decision picks digest vs flush at buffer end. `Daywatch::report()` re-rolls with `sampling.exceptions` so errors escape sampled-out traces.

**Why:** Errors are always worth recording even if the entire trace is sampled out. The re-roll ensures nothing is lost due to sampling bias.
