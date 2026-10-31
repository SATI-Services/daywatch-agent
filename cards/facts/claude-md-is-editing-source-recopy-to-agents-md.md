---
title: CLAUDE.md is the editing source — re-copy it to AGENTS.md after edits
tags: [repo, docs, gotcha]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: a914f6c40 2026-10-31
---
The engineering guide lives in two verbatim-identical copies: `CLAUDE.md` and `AGENTS.md`. `CLAUDE.md` is the editing source; after editing it, re-copy it to `AGENTS.md`. There is no shared parent-level `CLAUDE.md` — this file is the repo's engineering guide.

**Why:** Edits made only in `AGENTS.md` are silently overwritten the next time someone edits `CLAUDE.md` and re-copies.
