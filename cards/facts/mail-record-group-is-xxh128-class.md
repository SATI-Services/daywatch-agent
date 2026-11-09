---
title: MailRecord _group is xxh128(class)
tags: [records, wire-contract]
status: verified 2026-11-09 auto
source: [src/Records/MailRecord.php]
as_of: a6c4bee62 2026-11-08
---
MailRecord `_group` is xxh128(class), grouping messages by their mailable class.

**Why:** Allows aggregation of mail volumes and failures by message type.
