---
title: QueryRecord timestamp is query START, not finish
tags: [records, timing]
status: verified 2026-11-09 auto
source: [src/Records/QueryRecord.php]
as_of: 20af138a0 2026-11-09
---
QueryRecord.timestamp is the query START time (now − duration), not the finish.

**Why:** The `query` record is documented in `daywatch/docs/data-model.md` §2, and its field names are WIRE CONTRACT.
