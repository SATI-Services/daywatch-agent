---
title: ExceptionTrace inlines source code for at most 10 app-file frames
tags: [exception, records, limits]
status: verified 2026-11-08 auto
source: [src/Records/ExceptionTrace.php]
as_of: 2cf41c87a 2026-11-08
---
`ExceptionTrace::build()` inlines source code (±5 context lines) for at most 10 frames when `capture_exception_source_code` is true, and only for app files: a frame whose file is outside the app root or under `vendor/` gets `code: null` and does not count against the 10-frame budget. A file that is missing, unreadable, or larger than 2,000,000 bytes also yields `code: null`.
