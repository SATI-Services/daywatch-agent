---
title: /version renders current version in full; earlier versions condensed
tags: [harness, tooling, diagnostics]
status: verified 2026-10-26 auto
source: [.claude/skills/version/SKILL.md]
as_of: bd1f2a930 2026-10-26
---
In `/version`'s version breakdown, the current version shows its full feature bullets/paragraphs from the changelog; earlier versions are condensed to their headline plus the mechanisms they added (a few bullets each), listed newest-first. If the user asks about a specific earlier version (e.g. "what's in v2"), expand that one. If the current version is the only entry, say plainly: "v1.0 is the initial baseline — no earlier versions." Don't invent history. Keep it tight and terminal-friendly.

The breakdown follows the two-line "this project / latest" header, which leads the block — that header is the answer to "what am I on?".

**Why:** The point is a scannable history, not a wall of text.
