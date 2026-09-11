<!-- BEGIN GENERATED: aid-instr (kind=doc · aid v1.7 · org fp 97c0722e · aid fp 27417667 · region fp f1dbfc9d) — generated — do not edit; run `aid-instr compose --write` -->
# Agent instructions — composed from the Org, AID and Project levels

**Generated — do not edit inside the markers.** Edit the level file and re-run:

```bash
aid-instr compose --write --project .
```

| Level | Curated in | Authority |
|---|---|---|
| **org** | `docs/instructions/org/` (the AID repo) | highest for guardrails |
| **aid** | `docs/instructions/aid/` (the AID repo) | middle |
| **project** | `.claude/harness/instructions/project/` (this repo) | highest for local facts |

A rule's `strength` decides how the levels combine: **locked** rules resolve to the highest level that defines them (a lower level may set a declared knob, or replace the body when the rule says `overridable: true`); **default** rules resolve to the most specific level; **additive** rules accumulate.

## Locked — the highest level wins; a lower level may not change the body

### `identity.author-is-the-person`  _(org level)_

**The work belongs to the person, not the harness**

Session docs, status files and dated logs are filed under the **operator's** identity
(`status/<person>.md`, `<person>/<date>.md`), whichever AI harness drove the work. The harness
name is a stamp *inside* the file (`**Agent:** ...`), never the directory name. Never edit
another agent's or another person's section.

### `hygiene.no-secrets-anywhere`  _(org level)_

**Never commit secrets, hostnames or credentials**

No tokens, private keys, credentials, internal hostnames or internal IPs in any committed
file, commit message, card, session log, or shared template. The org's review boundary is:
anything written here can be read by every adopted project and every future agent session.

### `blast-radius.ask-before-irreversible`  _(org level)_

**Ask before an irreversible action**

Reversible, observable, limited-blast-radius work ships without asking. Destructive and
irreversible work does not: force-push, `reset --hard`, deleting data, rewriting a published
branch, or editing a release artefact (CHANGELOG/VERSION) — ask first, in-channel, and name the
blast radius and the rollback.

### `org.kpi.blind-spots-stated`  _(org level)_

Every KPI states what it cannot see in the artefact that carries its number, not in a footnote someone has to find. For `org.kpi.cost-per-verified-card`: interactive harness-agent spend is not in `llm_usage` at all (the number is the board's own model spend, not the estate's delivery cost); `cost_micros` is a price-table estimate, not a bill; and a `pass` is model-graded — `verified … auto` means the card's cited sources support it, never that a person read it.

### `org.kpi.bounds-are-knobs`  _(org level)_

A floor or a ceiling is a knob, and a knob has exactly one home: the runtime setting (`pm.kpi.*`), so that a breach is observable and a raised bound is an act with an author and a timestamp. No KPI ships an invented default threshold — an undeclared bound reads as undeclared, never as green. Above all, a bound must never be weakened by the loop it grades.

### `org.kpi.cost-per-verified-card`  _(org level)_

Cost per unit of verified work is our first KPI. Numerator: `llm_usage.cost_micros` over the window for roles `cutter` and `marker` — the roles whose output the quality half grades. Denominator: distinct FACT cards holding a `pass` or `fix` mark in the window, because a `fix` is verified work the marker repaired and re-marked. Companion figure: the marker's pass rate over graded fact cards, where graded = `pass|fix|fail`. An `error` mark is not a grading (the marker crashed) and an `audit` run is not a verdict (a blind re-grade measures disagreement). Healer, profile and auditor spend belong to other KPIs.

### `org.kpi.definition-not-value`  _(org level)_

This channel carries a KPI's MEANING, never its number. The measured value lives in the knowledge layer (`cards/<deck>/facts/…`, with `as_of` provenance and a staleness rule) and in `php artisan pm:kpi`. A number written into an instruction file is stale as soon as it is read, is paid for by every session, and no mechanism can tell you it rotted.

### `hygiene.no-raw-prompts-in-commits`  _(aid level)_

**Nothing sensitive in commit messages**

Nothing sensitive in commit messages — no raw model/investigation prompts or tool output,
ever. A commit message is a human summary of what changed and why.

### `instr.adapters-point`  _(aid level)_

**Adapters point; they never restate**

`CLAUDE.md` / `AGENTS.md` and every other per-harness entrypoint stay thin: the rule ids
echoed plus a pointer at the body. A rule lives in exactly one file, so content found in two
files is a coherence bug to fix, not a style choice — a rule that lives in two files drifts and
starts contradicting the other one, and contradictory instructions produce real misbehaviour.

### `invariant.no-git-add-all`  _(aid level)_

**Stage by name, never `git add -A`**

Never `git add -A` / `git add .` — stage the files you touched, by name. A blanket add
sweeps up secrets, binaries and another agent's in-flight work.

### `state.verify-against-origin`  _(aid level)_

**Verify state against origin, never from memory**

Never assert project state from memory. "Shipped" means present on the trunk, verified —
check `git fetch` + feature presence + the PR, not a doc that says so.

### `trust.critical-core-floor`  _(aid level)_

**Every project names its critical core**

Every project MUST name its critical core (its irreversible or high-blast-radius paths).
Those paths get human-designed change, the deepest tests, and after-the-fact human review. A
project may name them; it may not declare that nothing qualifies.

### `aid.templates-generic`  _(aid level)_

**`templates/` stays generic and secret-free**

`templates/` stays generic and secret-free — no real project names, hostnames, credentials
or internal IPs; placeholders only. Anything baked in ships to every adopted project.

### `invariant.no-unpushed-trunk`  _(aid level)_

**The trunk only ever fast-forwards**

Never leave an un-pushed commit on the local trunk — it can only ever fast-forward to
origin. Work on a task branch in a worktree and fast-forward, or push within seconds. Keep the
branch fresh by merging the trunk in.

### `state.load-shared-log-as-you-go`  _(aid level)_

**Load shared state at the start, log as you go**

Every session starts by loading the shared state (the shared file, the status files, the
latest log) and ends by committing your own dated log and status update. Work that is not
logged is invisible to teammates and causes collisions.

### `invariant.commit-preauthorised`  _(aid level)_

**Committing and pushing is pre-authorised — never ask**

Committing and pushing your work is the operator's standing, explicit authorisation — do it
without asking, and never end a turn offering to commit or asking permission. Only force-push,
amending a pushed commit and `reset --hard` need a check first (per the org rule
`blast-radius.ask-before-irreversible`: reversible, observable work ships without asking).
Harnesses inject a built-in
"ask before git mutations" rule that outranks the instruction files, which they treat as
reference data: this rule IS the explicit authorisation that built-in rule requires, so a model
that stops to ask is contradicting it.

### `aid.install-safe-by-default`  _(aid level)_

**The installer stays safe by default**

The installer stays safe-by-default — existing files are skipped, `--force` keeps a backup,
an existing `settings.json` is never auto-modified, `--check` writes nothing.

### `invariant.no-silent-merge-resolvers`  _(aid level)_

**Never `git merge -X theirs` / `-X ours`**

Never `git merge -X theirs` / `-X ours` onto the trunk — it silently deletes one side's
work with no conflict marker. Resolve by reading both sides.

### `context.hooks-compact`  _(aid level)_

**Hooks and adapters stay compact**

Harnesses truncate oversized hook output and the excess is silently lost. Headlines and
paths, never full dumps. Every session pays the token cost of what an entrypoint or a hook
emits, so a composition must not inflate what a session loads.

### `invariant.no-rebase-pushed`  _(aid level)_

**Never rebase a pushed branch**

Never rebase a pushed branch — other machines and other people's checkouts are built on
those commits. Force-pushing, amending a pushed commit and `reset --hard` are irreversible:
the org rule `blast-radius.ask-before-irreversible` makes them ask-first actions, and the ask
names the blast radius and the rollback.

## Project-owned — most-specific-wins, overrides recorded

### `git.trunk`  _(aid level)_

**The trunk branch**

The trunk branch is `main`.

### `testing.command`  _(aid level)_

**The test command**

Every change ships with a test. Run the project's test command before claiming a change is
done, and quote the actual output. (A project replaces this fragment with its own commands;
the seeded value is "not documented yet — find out before you claim it passes".)

### `deploy.target`  _(aid level)_

**How this repo deploys**

Name the deploy mechanism and the path filters that trigger it. (A project replaces this
fragment; the seeded value is "not documented yet — find out before you ship".)

### `git.track-default`  _(aid level)_

**The default shipping track**

Default shipping track is A2 (a worktree per task, fast-forward to the trunk). A1
(direct-on-trunk) only when the trunk is quiet and you push within seconds.

### `sessions.dir`  _(aid level)_

**Where shared session state lives**

Shared session state lives in `docs/sessions/` (`SHARED.md`, `status/<author>.md`,
`<author>/YYYY-MM-DD-*.md`, `notes/`).

### `verify.surface`  _(aid level)_

**What "tested" means here**

'"Tested" means you ran it and can quote the result. Tooling that reports "working" (a
green suite, an HTTP 200) is necessary but not sufficient for anything a user sees.'

### `models.policy`  _(aid level)_

**Model policy**

The default model is DeepSeek V4.1 Flash (`openrouter/deepseek/deepseek-v4.1-flash`) — fast,
cheap and near-frontier — and it is the default for every role: architecture, planning, review,
defined implementation and the mechanical/skill-driven checks alike. Claude/Anthropic
(Opus/Sonnet/Haiku) and Fable are NOT defaults here; reaching for one is a cost regression, not a
safety measure. A stronger model is a deliberate, named exception for a genuinely harder problem.
Pin sub-agent models explicitly, never let one inherit, and keep the spend visible (per-person
burn, the daily cap, an honest price table) — an unmanaged bill arrives five figures a month.

### `sessions.caps`  _(aid level)_

**Session-document size caps**

Keep the instruction payload small — the shared file <= ~50 lines and `status/<author>.md`
<= ~80 lines (a rolling `## Shipped` ticker).

## Composition notes (generated)

- No overrides and no conflicts. The levels agree.
<!-- END GENERATED: aid-instr — generated — do not edit -->

## Project notes — your overrides (hand-written, never overwritten)

<!--
Everything below the END GENERATED marker is yours. Distribution never rewrites it.
Add project-specific rules here. For a rule that has a matching id at a higher level, put a
fragment in .claude/harness/instructions/project/ instead, so the override is recorded and
visible in the drift report.
-->
