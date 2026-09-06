---
name: session-docs
description: The operational checklist for this project's shared session-memory system — writing session logs, status files, inter-author notes, and publishing them to the trunk safely. Use this whenever you write or update anything under docs/sessions/ (a dated log, your status file, SHARED.md, a note to a teammate), whenever you finish a work chunk or end a session, whenever you're asked to "let <person> know" something or mark someone's task done, and whenever you commit documentation. Also use it at session start if no briefing hook fired.
---

# Session Docs — the hive-mind discipline

> Generic process skill. `main` = your trunk branch (some repos call it `master`). Policy
> lives in `docs/WORKFLOW.md` → "Session management" (canonical; **if this skill ever disagrees
> with it, WORKFLOW.md wins — and fix this skill**). This skill is the *executable* version: exact
> paths, templates, and command sequences, so any model — including a lighter one — produces the
> same moves instead of re-deriving them from prose.

## Who am I?

`git config user.name` → author slug (lowercase first name): matches your `docs/sessions/status/<slug>.md`.
Your slug picks which files are *yours*.

## The file map + the one collision rule

| File | What it's for | Who writes it |
|---|---|---|
| `docs/sessions/<you>/YYYY-MM-DD-<topic>.md` | dated log — full findings archive | you |
| `docs/sessions/status/<you>.md` | your live state: `## In flight` (≤2) · `## Next up` (ordered backlog) · `## Open follow-ups` · `## Shipped`. **≤ ~80 lines total** — `## Shipped` is a rolling ~8-item ticker; prune older lands (dated logs are the archive) | **only you — ever** |
| `docs/sessions/SHARED.md` | cross-cutting only (blockers, priorities, transients) | anyone, append-mostly |
| `docs/sessions/notes/YYYY-MM-DD-<from>-to-<to>-<slug>.md` | inter-author messages | sender |

**The write-partition rule:** you never edit another author's `status/<author>.md` — not to mark
their task done, not to fix a typo in their claim, not "just this once". It's what makes concurrent
multi-agent sessions collision-free. To tell someone something, change their backlog, or correct
their stale claim: **write a note** (below). They fold it into their own file next session. (Stale
claims you can *evidence*: also fine to correct in `SHARED.md` or flag in your own log — see the
`status-reconcile` skill.)

## Your backlog lives in `## Next up`

`## Next up` in your own status file **is** your backlog: an *ordered* list, top = do-next. It's the
single deterministic answer to "what should I work on next". How work gets there:

- You decide it while working → add it yourself.
- The lead or another author assigns it → they **drop a note in `notes/` addressed to you** (the
  write-partition rule means they don't edit your status file). You triage the note into your
  `## Next up`, then archive it.

So: **inbox = newly-assigned, not-yet-triaged; `## Next up` = your owned, ordered queue.** Keeping
them distinct is what lets the lead reconcile the whole team's backlog without asking anyone.

## Notes — how anything crosses author boundaries

- Filename: `docs/sessions/notes/2026-07-02-<from>-to-<to>-<short-slug>.md` (multiple recipients:
  `-to-josh-kirill-`).
- Shape: `# <From> → <To> — <one-line subject>`, then `**<date>.**` + the message. Link evidence and
  any dated log with relative links. Sign off `— <you> (drafted by <harness>, <you>'s session)`.
- A note asks or informs; it does not edit the recipient's files for them.
- **Your inbox** (notes addressed to you): act on it, fold tasks into your own `## Next up`, then
  `git mv` the note to `notes/archive/` in the same commit.

## During the session

Logs are **skimmed, not read**: headline first, short sections, one line per commit, no restating
what the diff already says. A padded log is worse than a terse one — the reader who has to wade
gives up, and the log stops being memory. Capture every finding, but in the fewest words that keep
it re-verifiable.

- After each commit: one line in the dated log (hash + what + why).
- Findings, decisions, and *why a direction changed* go in the log the moment they happen — not
  reconstructed at the end.
- Keep `status/<you>.md` current: start something → `## In flight`; land it → `## Shipped` (with
  evidence: sha / PR # / route — see `status-reconcile` for what counts); new follow-up → `## Next up`.
- Dates absolute (`2026-07-02`); every claim carries evidence the next agent can re-check in one command.

## Session end checklist

1. Dated log complete (commits, findings, open items)?
2. `status/<you>.md` reflects end-state? (`In flight` empty or true; `Shipped` evidenced; whole file ≤ ~80 lines — if the tick warned you, trim `## Shipped` to the recent ~8 and drop merged `## In flight` items now)
3. Anything cross-cutting → appended to `SHARED.md`? Anything for a teammate → note written?
4. Commit + push all of it to `main` (next section) — **even if your code is on a Track B
   branch** (code on the branch; docs on `main`; log references the branch + HEAD sha).

## Publishing docs to `main` — the exact sequence

Your local `main` is read-only (it only fast-forwards). Docs commits go through a throwaway
worktree (Track A2). Doc-only pushes don't trigger deploys (the deploy `paths-ignore`s docs).

```bash
git fetch origin
git worktree add -b <you>/<topic>-docs ../wt-docs origin/main
# write/copy the doc files into ../wt-docs, then:
cd ../wt-docs
git add docs/sessions/<you>/2026-07-02-<topic>.md docs/sessions/status/<you>.md \
        docs/sessions/notes/2026-07-02-<you>-to-<them>-<slug>.md   # stage BY NAME — never git add -A / .
git commit -m "docs(session): <you> — <one-line theme>

Co-Authored-By: <your agent trailer>"
git push origin HEAD:main        # rejected? git fetch && git merge origin/main, re-push
cd - && git worktree remove ../wt-docs
git pull --ff-only                   # bring your local main up to date
```

**If `git pull --ff-only` fails** ("not possible to fast-forward"): someone (often a hook) committed
directly on local `main`. Don't reset, don't force. Inspect it (`git log origin/main..main`);
if it's a docs commit worth keeping, `git rebase origin/main && git push origin main`; if you
don't recognise it, surface it to the user before touching it.

## Session start (only if no briefing was injected by the harness hook)

Read, in order: `SHARED.md` → your `status/<you>.md` → the notes inbox (yours → act + archive) →
`gh pr list` → the latest dated log. Open with a ≤8-line briefing (state / priorities / in-flight /
next-up / your inbox), then do the user's ask. Full spec: WORKFLOW.md → "Session management".

## Templates

Dated log skeleton:

```markdown
# YYYY-MM-DD — <topic>
**Author:** <You> · **Track:** A2 (docs-only → `main`) | B (branch `<name>`, HEAD `<sha>`)
## Objective
## Findings / work log
## Output (commits, notes, PRs — with evidence)
## Open items
```

Note skeleton:

```markdown
# <From> → <To> — <subject>
**YYYY-MM-DD.** <message; evidence links; explicit asks as bullets>
— <From> (drafted by <harness>, <From>'s session)
```
