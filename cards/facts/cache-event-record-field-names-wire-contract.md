---
title: cache-event record field names are wire contract
tags: [wire-contract, cache, records]
status: verified 2026-11-08 auto
source: [src/Records/CacheEventRecord.php]
as_of: f86878bca 2026-11-08
---
`CacheEventRecord::toArray()` returns the envelope's `cache-event` child head — built by `Envelope::child('cache-event', Group::cache($this->store, $this->key))`, whose `_group` is `xxh128(store|key)` — merged with the record's own fields: `store`, `key`, `type`, `duration`, `ttl`. The docblock states the field names are WIRE CONTRACT.

**Why:** The `cache-event` record is agent protocol §2, and the docblock marks its names as wire contract — renaming any of them is a breaking schema change for the ingest side.
