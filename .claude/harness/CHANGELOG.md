# AID (AI-Led Delivery) — Changelog

The version history of **AID**, the AI-led delivery harness. Each version is a meaningful **release** of
the shared operating model + templates — bumped when a rollout adds or changes mechanisms projects should
know they're getting, not for every template tweak.

- **Versioning:** `major.minor` (`v1.0`, `v1.1`, `v2.0`, …). Bump the **minor** for additive
  mechanisms/skills; the **major** for a change in the model itself or a break in how projects adopt it.
  The canonical latest lives in the root [`VERSION`](VERSION) file.
- **How a project records its version:** `install.sh` writes `.claude/harness/VERSION` into the target
  (the version applied, the date, and the source harness commit) and ships a copy of this changelog to
  `.claude/harness/CHANGELOG.md`. Run **`/version`** inside a project to see what it's running and what
  each version added (like Claude's `/stats`).
- **Bumping:** when a rollout is significant enough to be a new version, add a `## vN.M` section at the
  TOP of the version list below, bump the root `VERSION`, and re-apply to projects (which re-stamps
  them). Keep entries newest-first.

---

<!-- Add the next release BELOW this line, at the TOP of the list (newest-first), as "## vN.M — YYYY-MM-DD — <headline>", then bump ./VERSION. -->

## v1.5 — 2026-09-06 — Board coordination: live presence + claims as doctrine

The session-log layer (git, 15-minute sync) is the durable memory; v1.5 adds the **live** layer
to the operating manual: agents coordinate through the AID Board's MCP so duplicated work stops
*before* it starts. `WORKFLOW.md` gains a "Board coordination" section (template + self):
session-start `list-tasks` (what you hold, what's claimed — don't start a second copy),
`claim-task` before working (the board's single source of "someone is on this" — the healer
honors it too), `post-activity` beats while working, `advance-task` with evidence to finish.
MCP at the board's `/mcp` endpoint, token per-operator from the secrets vault. Rolls out with
the next `install.sh --force` re-apply per repo.

## v1.4 — 2026-09-05 — Kimi hooks self-provision at session start

v1.3 shipped the Kimi Code session-logging hooks; v1.4 makes them install themselves, so the
convention propagates to every agent without operator action. Adopted projects now carry
**`kimi-install.sh`** in `.claude/hooks/`, and the `AGENTS.md` bootstrap runs it at every session
start: on a Kimi Code machine it installs/updates the per-operator hooks in `~/.kimi-code/`
(scripts refreshed on drift, the two `[[hooks]]` blocks upserted into `config.toml` with a `.bak`
backup, events already wired to another command left untouched) and prints `READY` — the agent
then tells the operator to start a fresh session, because hooks load at session start. On
non-Kimi machines it no-ops; `--check` reports status. `install.sh` now ships the kimi set
(pair + installer + `kimi-config.example.toml`) into projects — still never wired into
`settings.json`, activation stays per operator. Installed names drop the `kimi-` prefix (the
prefix exists only to disambiguate inside `.claude/hooks/`); the installer migrates the
short-lived v1.3 prefixed paths.

## v1.3 — 2026-09-05 — Kimi Code session-logging hooks (per-operator)

Kimi Code only appends hook output to the model's context on `UserPromptSubmit` — `SessionStart`
is observation-only — so the Claude wiring doesn't carry over. v1.3 ships the Kimi port of the
session-logging automation in `templates/hooks/`: **`kimi-session-log-nudge.sh`** (UserPromptSubmit
reminder, throttled ~15 min per project), **`kimi-log-commit.sh`** (SessionEnd safety-net commit of
the session-log dir — stage-by-name only, backs off when any unrelated change is present, pushes
only on `main`), and **`kimi-config.example.toml`**. The install is **per operator**
(`~/.kimi-code/`), not per project: the scripts detect the `docs/sessions/status` or
`sessions/status` convention per project, so one install covers every AID project the operator
runs. `templates/entrypoints/AGENTS.md` and `templates/WORKFLOW.md` carry the convention note so it
propagates to adopted projects on the next apply. Hooks remain a safety net — the agent still
writes the dated log. (Imported from a live Kimi deployment; `kimi-` prefixed because
`log-commit.sh` here is the Claude Code PostToolUse hook — different event, different job.)

## v1.2 — 2026-09-05 — Frictionless, and the AID Board as the dashboard

The doctrine correction and the product that runs it. **v1.1's release lanes are gone** — review
gates, approval queues, and dev-owned releases made developers the bottleneck, the exact failure
they were meant to prevent.

**Frictionless.** Anyone can do anything *safely* — reversible, observable, limited blast radius.
No dev gates anything; safety comes from **tests, instant rollback, and the regression net**, never
from permission. Blast radius still matters — as *attention* (irreversible paths get human-designed
changes and the deepest tests), never as a gate. Developers repair things and enhance quality; they
do not slow the pace of development. The objection standard survives in spirit: nervousness becomes
a control (flag, monitor, cap), never a gate. The board's own signoff gate was removed the same day
(`config/pm.php` gates emptied; agents complete the full loop).

**The AID Board becomes the dashboard** (first-principles: an AID project is five things — a GitHub
repo with session logs, people × agents, tests + interval E2Es, frictionless, push-to-main — and the
system answers three questions: who's working on what with which agents, how current it is, and
whether it has regressed against baseline). Kanban demoted to `/board`. New: the **project registry**
(self-maintaining — seeds from decks + self + a GitHub hunt for the `.claude/harness/VERSION`
stamp; push age / CI / scheduled-E2E state per project), the **presence stream** (`post-activity`
MCP tool + dashboard pulse; ephemeral coordination — git session logs stay the durable memory),
**Jira/GChat link fields** per project, a **queue project filter**, **Google Workspace SSO**
(Socialite, hosted-domain enforced server-side, auto-provisioned humans, no passwords), and the
**v2 design pass** (dark-first theme + toggle, brand + favicon, self-hosted fonts).

**Process:** everything above was built on the board itself through the lanes-free loop — claim →
refine → implement → review (evidence) → testing (live-verify) → signoff by the builder. Full
narrative in `docs/sessions/will/2026-09-05-harness-self-application-v1-1.md`.

## v1.1 — 2026-09-05 — Release lanes, guardrails over gates, and the cards layer

The release-side companion to the trust gradient, plus the compaction layer that landed since v1.0.

**Release lanes (green / amber / red).** The default flips from "prove it's safe" to "proceed unless
someone names a specific, material, insufficiently controlled risk." Green (reversible, observable,
limited blast radius) ships builder-released on Track A with no dev involvement; amber (reversible but
production-touching) ships with a **time-boxed** dev review (24h SLA — a clock, not a queue); red
(permanent data alteration, schema, auth/credentials, personal-data exposure/transfer,
consent/suppression/eligibility, comms at scale, money movement / irreversible third-party actions) is
dev-involved from the start and dev-owned at release, Track B. The red list is explicit, short, and
leadership-owned — the risk appetite written down, not invented per-developer.

**The objection standard.** "Might be unsafe" blocks nothing. A blocking or re-laning objection states
the precise failure, realistic blast radius, detectability, why it can't be rolled back, and the
smallest sufficient control. Nervousness that fails the test becomes a control (flag, monitor, cap),
not a gate.

**Guardrails over gates + the accountability split.** Developers provide the environment — sandboxes
with synthetic/protected data, read-only prod access, backups with *tested* restores, feature flags and
instant rollback, send-volume caps, audit logs, kill switches, standard APIs, reusable AI instructions —
not permission. Devs own the safety of the boundaries; builders own what they build inside them. Roles
re-scoped: business users as authorised AI builders (own usefulness); developers as system owners
measured on business-adjacent metrics (availability, incidents, lossiness, latency, cloud cost) plus
time-to-unblock / review turnaround on the rotating AI-support-dev shift; QA independent and
increasingly automated.

**The metrics-regression loop.** Pillar 5 widens from test suites to system metrics: lossiness /
latency / deliverability / cost run on the daily (or hourly) schedule; a regression caused by a change
gets an auto-remediation agent as first responder — same lanes as people (red-lane regressions stay
human), and a failed auto-fix still leaves a red run with a named same-day owner.

**Cards (the verified-compaction layer).** `cards/` — atomic, precise, flat cards in two kinds (facts,
human-verified before they load; todos, reconciled against git/prod), with `cards/split.py` generating
`_INDEX.md` and linting. The compact cross-harness memory under a project's growing long-form docs;
`promote: candidate` cards feed future backfills. (Landed in the repo since v1.0; first changelogged
here.)

**Self-application.** The harness now runs on its own repo: installed via `install.sh` with all
placeholders filled, and a `harness-check.yml` CI workflow (shellcheck + install-into-scratch +
`--check` + cards-index freshness) standing in for the daily E2E.

## v1.0 — 2026-07-08 — Initial baseline

The first formal version: the full operating model distilled from the PingPath reference
implementation, generalised into droppable templates, and proven across ddprai, Loan-Form-Hub, and
Platforms (the first ops/handbook target). Everything below is what a project gets on v1.

**Shared-memory PM system.** Plain-markdown project management that every agent reads at session start
and writes as it works — no separate ticketing tool. `SHARED.md` (thin cross-cutting state), per-author
`status/<author>.md` (`## In flight` ≤2 · `## Next up` ordered backlog · `## Open follow-ups` ·
`## Shipped` evidenced, kept to a rolling ~80-line cap), a `notes/` inter-author inbox, and never-trimmed
dated logs. Writes are partitioned by author so concurrent agents can't collide.

**One operating manual + thin adapters.** A single canonical `WORKFLOW.md` per project; each AI tool
(`CLAUDE.md`, `AGENTS.md`, `GEMINI.md`, `replit.md`) gets a *thin adapter* that points at it and adds
only tool-specific notes. The rule "adapters point, never restate" + a periodic coherence pass stop the
instruction files from drifting into contradictions.

**Push-to-main git workflow.** `main` is the staging environment; review happens on the diff after the
fact. Track A1 (direct-on-main, push in seconds), Track A2 (worktree-per-task — the fast-forward-only
invariant that stops `main` diverging), and Track B (branch + PR for critical-core / risky work).
Hot-file collision discipline: additive-only edits in contended files, a post-merge survival grep, and
a ban on `git merge -X theirs/ours` onto the trunk. Never `git add -A`.

**Deterministic automation (hooks).** `session-briefing.sh` (compact session-start briefing — git state,
priorities, per-author headlines, notes inbox, open PRs, daily-E2E), `session-tick.sh` (a visible
"hook fired" tick + daily-E2E ✅/❌ + a nudge when your own status file is over the length cap), and
`log-commit.sh` (auto-appends each commit to the day's log, by explicit pathspec).

**Executable process skills.** The recurring behaviours packaged as skills so a lighter/unattended model
performs them identically (measured on the reference project: ~61% → ~98% adherence): `session-docs`
(the shared-memory discipline + safe doc-publish sequence), `status-reconcile` ("docs are claims, git is
truth" — evidence hierarchy + verdict vocabulary), `live-verify` (validation: test as a real user on the
real surface and read the artifact — for projects with a user-facing surface), `parity-audit` (ledger-backed
enumerate-and-verify — for port/mirror projects), and `skill-bench` (regression-tests the skills themselves
on a light model against live ground truth).

**Layered testing + validation net.** Every change ships with a test; the "test as the user, on the real
surface — the artifact you read is the sign-off" validation doctrine; a code-reviewer sub-agent encoding
the project's invariants; a daily scheduled E2E with named same-day ownership of every red run. Designed
to catch the four failure classes an instant loop leaks (code-logic, data-shape, usability, design drift).

**Trust gradient.** Humans architect the critical core (anything writing to high-capacity/shared data
stores, schema, money/auth/data-integrity); AI builds the disposable surface (GUIs, dashboards, read-only
views) fast. Match the author and the standard to the blast radius.

**Operational policy.** A per-agent `agent-memory` pattern (reviewers compound knowledge over time); a
model & cost policy (match model to job, pin sub-agent models); environment & dependency pinning;
token/secret hygiene (scoped per-service tokens, nothing sensitive in commit messages).

**Adoption tooling.** `install.sh` (safe-by-default backfill with `--branch`, `--check`, placeholder
substitution) and this versioning layer (the `VERSION` stamp + `/version` skill + this changelog).

*Provenance:* distilled from PingPath (backfill #1, 2026-06-26); forward-applied to ddprai (#2, 2026-07-01)
and Loan-Form-Hub with a team-retro sweep (#3, 2026-07-03); PingPath's week-2 wins (status-file cap, the
validation doctrine, a skill-bench fix) folded in and applied to Platforms (#4, 2026-07-07). See
[`docs/`](docs/). These were the pre-release evolution; v1 is the point at which the model was frozen as
a numbered baseline.
