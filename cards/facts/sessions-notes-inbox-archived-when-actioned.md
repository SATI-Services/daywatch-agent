---
title: notes/ are inbox messages; git mv to notes/archive/ when actioned
tags: [session-logs, collaboration, inbox]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: d4813a7e6 2026-10-25
---
notes/ holds inter-author messages as YYYY-MM-DD-<from>-to-<to>-<slug>.md (one file per message). When you've actioned a note addressed to you, fold it into your status/<you>.md ## Next up, then `git mv` it to notes/archive/.

Archive state is a health signal: notes to you still sitting un-archived are the cheapest sign that a handoff isn't landing.

**Why:** Inbox discipline prevents delegation from silently failing; archive state is visible to the lead.
