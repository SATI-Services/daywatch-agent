---
title: RecordCounter scans payload at frame boundary without decoding
tags: [daemon, performance, buffer]
status: verified 2026-11-04 auto
source: [src/Daemon/RecordCounter.php]
as_of: 90ae942b6 2026-11-04
---
RecordCounter counts top-level JSON objects in a digest payload by scanning for `{` and `}` outside strings, never decoding. Single pass via strcspn/strpos jumps; counts nested structures (exceptions, trace arrays) as part of their record, not separately.

**Why:** The daemon never re-parses record JSON: counting is a single pass driven by C-speed primitives (strcspn/strpos), with no allocation and no parse tree. Count happens once, at the frame boundary.
