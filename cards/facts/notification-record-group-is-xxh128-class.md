---
title: NotificationRecord `_group` is xxh128(class)
tags: [records, wire-contract]
status: verified 2026-11-09 auto
source: [src/Records/NotificationRecord.php]
as_of: a6c4bee62 2026-11-08
---
NotificationRecord `_group` is `xxh128(class)`: `toArray()` emits the record as the `notification` child of the envelope, grouped by `Group::name($this->class)` — the notification's `class` field.

Field names are WIRE CONTRACT.
