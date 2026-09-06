---
name: version
description: Report which version of AID (the AI-led delivery harness) this project is running, and summarise what each version added — like Claude's /stats, but for AID. Use this whenever the user runs /version, or asks "what AID/harness version is this project on", "what's in this version", "what changed between versions", or "is the harness up to date here". Reads the local version stamp + shipped changelog and (if gh is available) compares against the canonical harness repo to flag whether a newer version exists.
---

# /version — AID version & feature summary

Report the version of **AID (AI-Led Delivery)** this project runs and what each version delivered. This
is a **read-only report** — you are not upgrading anything, just telling the user where they stand.
Produce the summary yourself (run the checks); don't hand the user commands to run.

Versions are `major.minor` (e.g. `v1.0`). Render them with the leading `v`.

## Inputs (all local, no network required for the core report)

1. **The project's version stamp** — `.claude/harness/VERSION`. Key/value text:
   ```
   version: <N>
   applied: <YYYY-MM-DD>
   source: <ORG/REPO of the harness>
   source_commit: <short sha the applied content came from>
   notes: <optional — e.g. "right-sized: no live-verify/parity-audit">
   ```
   If this file is **missing**, the project predates versioning (or was set up by hand). Say so, and
   tell the user they can stamp it by re-running the harness `install.sh` (or adding the file manually);
   then fall back to reporting from the changelog's newest entry as the assumed version.
2. **The shipped changelog** — `.claude/harness/CHANGELOG.md` (a copy of the harness changelog captured
   at apply time). This is the source of the per-version feature summaries. If absent, read the project's
   `docs/WORKFLOW.md` header and say the changelog wasn't shipped with this install.

## Optional freshness check (only if `gh` is authenticated)

Read `source` from the stamp, then fetch the canonical latest version:
```bash
gh api "repos/<source>/contents/VERSION" --jq '.content' 2>/dev/null | base64 --decode
```
Compare it to the project's `version`. Report one of: **up to date** (equal), **behind — vX available**
(canonical is higher; mention `install.sh` re-applies + re-stamps), or **ahead / unknown** (local is
higher, or gh unavailable — just report the local version and skip the comparison silently on failure).
Never let a failed network/gh call break the core report.

## Output format (a `/stats`-style block)

```
## AID — AI-Led Delivery Harness

  This project    v<N.M>   (applied <date> · from <source>@<sha>)
  Latest          v<X.Y>   <✅ up to date | ⬆ upgrade available | — check unavailable>
  <one line of the stamp's `notes:` if present>

### v<N.M> — <date> — <headline>   ← current
<the feature bullets/paragraphs for the current version, from the changelog>

### v<N.M-prev> — <date> — <headline>
<summary — condense earlier versions to their headline + the notable additions, not the full text>

  …earlier versions, newest-first…
```

Rules for the render:
- **Current version in full; earlier versions condensed** to their headline plus the mechanisms they
  added (a few bullets each) — the point is a scannable history, not a wall of text. If the user asks
  "what's in v2" specifically, expand that one.
- If the current version is the only entry (no earlier versions), say so plainly: *"v1.0 is the initial
  baseline — no earlier versions."* Don't invent history.
- Keep it tight and terminal-friendly. Lead with the two-line "this project / latest" header — that's
  the answer to the most common question ("what am I on?"); the version breakdown follows.
