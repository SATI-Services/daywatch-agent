---
title: ExceptionTrace frames are file:line, source signature, and optional inlined code
tags: [exception, records, wire-contract]
status: verified 2026-11-08 auto
source: [src/Records/ExceptionTrace.php]
as_of: 2cf41c87a 2026-11-08
---
ExceptionTrace frames emit `{file:"path:line", source:"Class->method(argTypes)", code:{lineNo:"src"}|null}` where `file` is app-relative. `code` is present only when inlining is enabled (`capture_exception_source_code`, agent protocol §2), the file is an app file (not `vendor/`, not outside the app root), and fewer than 10 source-inlined frames have been emitted; each inlined frame carries ±5 context lines, and unreadable or >2,000,000-byte files yield no code.

**Why:** This format pairs location, callable signature, and optional inlined source into a minimal but complete frame representation for debugging.
