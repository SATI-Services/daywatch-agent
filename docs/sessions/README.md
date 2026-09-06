# Session Logs & Shared Memory

The cross-machine, cross-author, cross-agent context bridge — how any session (AI or human, any laptop) picks up where the last left off. Multiple people each run multiple agents concurrently, so the structure is designed to **avoid write-collisions: partition writes by author, read everyone.**

## Layout

```
docs/sessions/
├── SHARED.md             ← thin group-owned cross-cutting state (read first)
├── status/
│   └── <author>.md       ← each author's live WIP + follow-ups (ONLY they write it)
├── notes/
│   ├── YYYY-MM-DD-<from>-to-<to>-<slug>.md   ← inter-author messages (inbox)
│   └── archive/          ← notes that have been read + actioned
├── <author>/YYYY-MM-DD-*.md   ← dated session logs (the full archive)
└── README.md             ← this file
```

## The rules that prevent collisions

- **`SHARED.md`** holds *only* what concerns more than one person — shared blockers, prod-wide infra facts, credential rotations, a current-priorities board, a short recently-shipped ticker. Keep it ≤ ~50 lines; **append, don't rewrite** (appends merge cleanly).
- **`status/<author>.md`** holds your live work, in a fixed, skimmable schema: `## In flight` (≤2, what you're doing now) · `## Next up` (your ordered backlog) · `## Open follow-ups` (unscheduled) · `## Shipped` (evidenced done-ticker). **No two authors write the same file**, so cross-author conflict is structurally impossible. If your own file conflicts, two of *your* sessions raced. **Keep the whole file ≤ ~80 lines:** `## Shipped` is a *rolling* ticker — the ~8 most-recent lands (or ~last 14 days), each with evidence; delete older entries (the dated logs are the permanent archive, so re-listing there is redundant) and move merged items out of `## In flight` promptly. The SessionStart tick warns you when your own file is over the cap; only you prune it (write-partition).
  - **`## Next up` is your backlog** — an ordered list, top = do-next; the single deterministic answer to "what should I work on next" (and what lets the lead reconcile the whole team's queue without asking). Work enters it two ways: you add it, or a teammate/lead **drops a note addressed to you** (they can't edit your status file — write-partition), and you triage that note into `## Next up`. So the *inbox* is newly-assigned-not-yet-triaged; `## Next up` is your owned queue.
  - `## Shipped` lines carry **evidence** (a sha, PR #, or route on the trunk). "PR open" is not shipped — it stays in `## In flight` until merged. The `status-reconcile` process skill is the checklist for what counts.
- **Who is `<author>`?** Always the **person** (from git identity — `will`, `josh`, ...), whichever harness or agent drove the work; the work is canonically theirs. Agents identify themselves *inside* the files: the `**Agent:**` field in dated logs, and — when more than one agent works for the same person — a per-agent `## <harness>` section in `status/<author>.md` (same fixed schema inside it) — each agent edits only its own section, never another agent's or the operator's. Concurrent agents of the same person should be on Track A2 worktrees: disjoint sections merge cleanly, and that — not per-agent filenames — is what prevents file races.
- **`notes/`** — leave a teammate context as `YYYY-MM-DD-<from>-to-<to>-<slug>.md`. One file per message. When you've actioned one addressed to you, fold it into your own `status/<you>.md` `## Next up`, then `git mv` it to `notes/archive/`. **The archive state is a health signal:** notes to you still sitting un-archived are the cheapest sign that a handoff isn't landing — the lead reads the inbox as their check on whether delegation is working, so keep yours drained.
- **dated logs** are the never-trimmed archive. Update them *as you go*, not at the end.

> **This discipline is also an executable skill.** The exact paths, templates, and the Track-A2
> command sequence for publishing docs to the trunk live in the `session-docs` process skill
> (`.claude/skills/session-docs/`), so a lighter model performs the same moves. This README is the
> contract; the skill is the runnable version of it.

**Keep your status current in the same push as the work.** Your `status/<you>.md` and a meaningful commit subject are what let others (and the briefing hook) reconcile what you're doing without asking. A terse subject (`push`, `wip`) plus a stale status file is how two people end up building the same thing — update both when you push, not later.

## Document the why, not just the what

Git captures the *what*. These logs are the only place the *why* survives: why this approach over the alternative, what it was tested against (env, real round-trip vs fixtures, suite green?), what's deliberately deferred and why, what's still gated. A log written as you go is two minutes; reconstructing it from a diff later is a morning and often wrong.

## Dated log template

```markdown
# Session: <title>
**Date:** YYYY-MM-DD   **Author:** <author>   **Agent:** <harness>   **Track:** A (main) | B (branch <name>)

## Objective
What we set out to do.

## Key findings
Discoveries, root causes, decisions (with the why).

## Actions taken
Code/config changes, follow-ups. Reference commit hashes.

## Tested against
Which environment, real data or fixtures, which suites, green or not.

## Open items
Anything unresolved — mirror live ones into status/<you>.md (or SHARED.md if cross-cutting).
```
