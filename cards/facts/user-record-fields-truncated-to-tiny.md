---
title: UserRecord id, name, and username are truncated with Truncate::tiny
tags: [records, limits]
status: verified 2026-11-10 auto
source: [src/Records/UserRecord.php]
as_of: ebeca95d9 2026-11-10
---
`UserRecord::toArray()` applies `Truncate::tiny()` to the `id`, `name`, and `username` fields before emission.
