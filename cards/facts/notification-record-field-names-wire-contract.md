---
title: NotificationRecord field names are wire contract
tags: [wire-contract, records]
status: verified 2026-11-09 auto
source: [src/Records/NotificationRecord.php]
as_of: a6c4bee62 2026-11-08
---
`NotificationRecord`'s field names are WIRE CONTRACT; do not add or rename them. The wire fields are `channel`, `class`, `duration`, `failed`, plus the envelope's `_group` child.

**Why:** It is the `notification` record of the agent protocol (§2), emitted on NotificationSending→NotificationSent.
