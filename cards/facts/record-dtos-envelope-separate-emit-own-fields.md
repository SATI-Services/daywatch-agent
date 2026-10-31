---
title: Record DTOs declare Envelope and emit only their own fields; EnvelopeTest enforces
tags: [records, wire-contract, structure]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
Record DTOs declare `Envelope $envelope` and emit **only their own fields**; sensors build one with `Envelope::for($core)`. `EnvelopeTest` enforces this — a DTO that re-inlines the envelope fails the build.

**Why:** All shared wire fields live in `Records/Envelope.php`; a new record type picks a head shape and appends only its own fields, exactly one head shape per DTO.
