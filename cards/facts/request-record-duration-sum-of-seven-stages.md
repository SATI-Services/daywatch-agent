---
title: RequestRecord duration is the sum of seven execution stages
tags: [record, request, metrics]
status: verified 2026-11-08 auto
source: [src/Records/RequestRecord.php]
as_of: c34aba0b3 2026-11-08
---
RequestRecord duration is array_sum(stages) of ExecutionStage::REQUEST_STAGES: bootstrap, before_middleware, action, render, after_middleware, sending, terminating.

**Why:** Total request time is the sum of all seven lifecycle stages.
