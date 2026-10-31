---
title: The daywatch:agent daemon keeps at most 5 in-flight POSTs
tags: [daemon, dispatch]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
`daywatch:agent` allows at most 5 POSTs to the ingest to be in flight at once.
