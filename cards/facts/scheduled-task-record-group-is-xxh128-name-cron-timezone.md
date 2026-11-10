---
title: ScheduledTaskRecord _group is xxh128(name|cron|timezone)
tags: [records, grouping]
status: verified 2026-11-10 auto
source: [src/Records/ScheduledTaskRecord.php]
as_of: 72d7040e7 2026-11-09
---
`ScheduledTaskRecord::toArray()` sets `_group` via `Group::scheduledTask($this->name, $this->cron, $this->timezone)`; the record's docblock states `_group = xxh128(name|cron|timezone)`.

**Why:** All three task-identity fields are passed to `Group::scheduledTask()`, so the group key changes when any of name, cron or timezone changes.
