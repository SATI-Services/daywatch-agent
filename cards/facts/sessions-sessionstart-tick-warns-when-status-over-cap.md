---
title: SessionStart tick warns when your status file passes ~80 lines; only you prune it
tags: [session-logs, status, sessionstart]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
The SessionStart tick warns you when your own `status/<author>.md` is over the ~80-line cap. Only the author prunes it — write-partition means nobody else edits the file.

**Why:** The cap keeps status files skimmable; the tick is the enforcement hook, and ownership of pruning stays with the author.
