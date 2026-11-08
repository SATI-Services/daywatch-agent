---
title: RequestRecord route_methods is an array, not a single string
tags: [record, request]
status: verified 2026-11-08 auto
source: [src/Records/RequestRecord.php]
as_of: c34aba0b3 2026-11-08
---
RequestRecord.toArray() emits route_methods as array_values($this->routeMethods), preserving all matched verbs.

**Why:** A route may match multiple HTTP methods; the array captures them all.
