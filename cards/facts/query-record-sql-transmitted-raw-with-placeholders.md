---
title: QueryRecord sql transmitted raw with ? placeholders, bindings never substituted
tags: [wire-contract, records]
status: verified 2026-11-09 auto
source: [src/Records/QueryRecord.php]
as_of: 20af138a0 2026-11-09
---
QueryRecord.sql is transmitted raw with `?` placeholders — bindings are never substituted.
