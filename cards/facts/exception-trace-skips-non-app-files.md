---
title: ExceptionTrace inlines source only for app files, never vendor/ or outside the app root
tags: [exception, records, scope]
status: verified 2026-11-08 auto
source: [src/Records/ExceptionTrace.php]
as_of: 2cf41c87a 2026-11-08
---
`ExceptionTrace::build()` sets a frame's `code` source only when `isAppFile($file)` passes: `Location::appRelative($file)` must shorten the path (a path that is still absolute is outside the app root) and the relative path must not start with `vendor/`. Frames that fail the check get `code: null`.

**Why:** The class docblock scopes source inlining to "app files only"; vendor/ paths are treated as framework code.
