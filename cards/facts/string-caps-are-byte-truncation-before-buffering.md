---
title: String caps are byte truncation (255B/64KB/16MB tiers) before buffering
tags: [truncation, buffer, protocol, sizing]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
String field caps are byte truncation (255 B / 64 KB / 16 MB tiers) applied before buffering. Durations are integer microseconds. Redaction happens in-app (the privacy boundary), never downstream.

**Why:** Byte truncation is the only safe cap for UTF-8 strings. Redaction at source ensures sensitive data never leaves the host.
