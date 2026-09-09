---
title: Record DTOs declare Envelope and emit only their own fields; EnvelopeTest enforces
tags: [records, envelope, wire-contract, testing]
status: verified 2026-09-09 auto
source: [CLAUDE.md, docs/WORKFLOW.md]
as_of: 16a6c506a 2026-09-06
---
Every record DTO declares an `Envelope $envelope` and emits only its own fields; the shared head shapes (child/execution/minimal) live in `Records/Envelope.php`. `EnvelopeTest` fails the build if a DTO re-inlines the envelope, enforcing the separation.

**Why:** The envelope is shared wire mapping across all record types. Centralizing it in one place prevents field drift and makes contract changes testable.
