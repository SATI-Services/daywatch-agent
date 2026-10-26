---
title: 'Multiple agents for one person get a per-agent ## <harness> section in status/<author>.md'
tags: [session-logs, status, agents]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
When more than one agent works for the same person, `status/<author>.md` carries a per-agent `## <harness>` section using the same fixed schema inside it. Each agent edits only its own section — never another agent's or the operator's.

**Why:** `<author>` is the person from git identity, so that person's concurrent agents share one status file; per-agent sections keep their writes disjoint.
