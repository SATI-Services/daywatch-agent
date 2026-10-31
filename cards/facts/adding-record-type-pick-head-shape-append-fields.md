---
title: Adding a record type = pick a head shape, append your fields, done — never re-inline the envelope
tags: [records, architecture, pattern]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
Adding a record type means: pick a head shape, append your fields, done — never re-inline the envelope.

**Why:** Re-inlining the envelope duplicates shared logic and breaks the invariant that `EnvelopeTest` enforces.
