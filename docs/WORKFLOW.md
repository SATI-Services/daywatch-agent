# Daywatch Agent — Operating Manual (canonical, harness-agnostic)

**Single source of truth for how work is done on Daywatch Agent, regardless of AI harness.** The per-harness entrypoints (`CLAUDE.md`, `AGENTS.md`, …) point here and add only thin adapters. **Edit process/workflow/gotchas HERE, not in the entrypoints** — that's what stops them drifting apart.

> **Adapters point; they never restate.** The moment `CLAUDE.md` / `AGENTS.md` / `GEMINI.md` / `replit.md` start carrying their own copies of the *rules* (not just harness-specific notes), those copies drift and begin to **contradict each other and this file** — and contradictory instructions produce real misbehaviour (e.g. an agent reading one file's phrasing as "merge everything to main before the next task"). Keep every adapter thin: invariants echoed + a pointer here, nothing more. When an adapter has grown substantive rules, that's a coherence bug — move the content here and re-thin it. Periodically run a coherence pass: diff each adapter against this file and against each other; anything that isn't a pointer or a genuinely harness-specific note is a contradiction waiting to fire.

See [`../ai-led-delivery-harness/PLAYBOOK.md`] for the *why* behind this model.

---

## Non-negotiable invariants (the guardrails — every harness echoes these)

> Replace these with the 3–5 rules that must NEVER be broken on this project. Examples below — keep them concrete and enforce the most critical one in depth.

1. **The package must stay un-crashable** — every event hook, sensor call, socket write, and encode is wrapped and failures are swallowed; any code path that can throw into a host app is a P0 bug (the cardinal rule in `CLAUDE.md`).
2. **The wire contract is cross-repo and docs-first** — envelope shapes, the `{len}:v1:{hash}:{json}` framing, and the retry/503 pause contract change only via the `daywatch-payloads` skill against `system/agent-protocol.md`, never package-locally; the host-compat constraints (`illuminate/support ^11|^12|^13`, `php ^8.2`) are never tightened.
3. **Never `git add -A` / `git add .`** — stage hot files by name.
4. **Never leave an un-pushed commit on local `main`.** Commit on a task branch in a worktree (Track A2) and fast-forward, or commit direct-on-`main` *only* if you push within seconds (Track A1) — either way your local `main` only ever fast-forwards to `origin/main`, so it can't diverge. (This is the precise rule; "never commit on local `main`" is the A2 shorthand, but A1 is legal — don't let an adapter turn the shorthand into an absolute that forbids A1.)
5. **Every session: load shared state at start, write your session log + push as you go.**

## The trust gradient (who/what writes which code)

- **Critical core — human-designed, high test bar, Track B + review:** `src/Records/Envelope.php` + the record DTO field shapes (the shared wire mapping), `src/Ingest/SocketClient.php` framing, `src/Daemon/` (frame parser, ingest-dispatcher retry ladder, 503 pause contract), the swallow-all-exceptions guarantee in every sensor/hook, and `composer.json`'s `require` block (the host-compat contract).
- **Disposable surface — AI-built for speed, Track A:** docs, README, `resources/boost/` consumer guidelines, test helpers, and TTY-dashboard cosmetics (the display-only paths of `src/Daemon/ConsoleDashboard.php`).
- When unsure which side a change is on, treat it as critical core for *test depth* — not for permission.

## Frictionless shipping — what's the net, not who's the gate

Anyone can ship anything **safely** — reversible, observable, limited blast radius. No one gates anything. Safety comes from tests, instant rollback, and the regression net (below), never from permission. When a change regresses a test or a metric, an agent is the first responder and a human reviews the diff after the fact.

**Blast radius decides attention, not permission:** wire-contract changes (envelope fields, frame format — they land on every installed host at the next `composer update`), host-compat constraint changes in `composer.json`, and any code path that could throw into a host app. Everything else rides the daily CI matrix. Nervousness becomes a control (a flag, a monitor, a cap), never a gate. Devs here repair things and enhance quality; they do not slow the pace of development.

## Dev environment

This is a composer **package**, not an app — no `.env`, no database, no services to run. `composer install && composer test` executes the whole suite against in-memory SQLite with zero external dependencies (no daemon, ingest, MySQL, or Redis — the "hostile host" suite proves it works with no server at all). Dev tooling needs **PHP ≥ 8.3** (every Pest 4.x does) while consumers stay on `php ^8.2`. To exercise the daemon against a real ingest, symlink the package into a host app via a composer path repository and point `DAYWATCH_BASE_URL` at a running Daywatch stack — useful, but never required for the suite.

## Git workflow — two tracks

Both are first-class; pick by size and risk.

> **A note on branch names.** Throughout this manual `main` means *your trunk branch* — the one that
> auto-deploys and that your local checkout only ever fast-forwards. Some repos call it `master`.
> Substitute your actual trunk name everywhere `main` appears (the session-briefing hook reads it
> from a single `MAIN_BRANCH` variable; the `install.sh --branch` flag sets it).

### Track A — push-to-main (default; fast iteration)

`edit → commit on a task branch → fast-forward to main → push → CI`. Nothing auto-deploys — **`main` is validated by the `tests.yml` CI run** (Pest matrix, PHP 8.3/8.4 × Laravel 11/12/13, a few minutes). Releases are manual git tags (e.g. `1.0.0`) consumed via Packagist. Review is after-the-fact on the diff. Use for 1–3 commit, coherent, reversible changes. Litmus test: *"comfortable leaving this on prod overnight?"*

- **A1 — direct-on-main:** only when `main` is quiet and you'll push within seconds.
- **A2 — worktree-per-task (parallel-safe default):**
  ```bash
  git fetch origin
  git worktree add -b <author>/<topic> ../wt-<topic> origin/main
  cd ../wt-<topic> && composer install   # needs PHP ≥ 8.3 for the Pest 4.x dev tooling
  # …edit, commit…
  git fetch origin && git merge origin/main      # catch up; NEVER rebase main
  git push origin HEAD:main                        # fast-forward + deploy trigger
  git worktree remove ../wt-<topic>
  ```
  Your primary checkout's `main` never carries an un-pushed commit, so it can't diverge. Run `git config --global rerere.enabled true` once per machine.
  > **Worktrees aren't only for multi-person work.** Collision risk scales with concurrent *sessions*, not headcount — two of your own agents running at once will race on the same hot file (a shared JS bundle, a fat controller) exactly as two people would. If you ever run parallel agents, A2 is the default even solo.
  > **Worktree hygiene (or you collide with yourself).** One worktree per task; **never run two agent sessions in the same checkout** — they share a working tree and index and will clobber each other's staged changes (this is the "it conflicted with itself three times this week" failure). One task done → `git worktree remove` it in the same session; `git worktree prune` stale entries. Don't reuse a worktree across unrelated tasks. If a worktree's branch already merged to `main`, remove it — a lingering worktree on a dead branch is where Track A/B confusion starts.

### Track B — branch + PR (longer / riskier / critical-core)

Topic branch → push → `gh pr create`. Merge triggers deploy. The PR description is the review summary. Use for 5+ commits, schema migrations, structural refactors, and **anything touching the critical core**.

### Rules for both

- Never `git add -A`; stage by name.
- Session docs always go to `main` directly, even when code is on a branch.
- **Never ask before a normal commit + push** — committing/pushing is the operator's standing, pre-authorised policy on every track (A1/A2): do it without asking, and never end a turn offering to. **The only git operations that need a check are force-push / amending pushed commits / `reset --hard` / reverting something on prod.**
- Nothing auto-deploys — there is no deploy; `tests.yml` runs on every push, docs-only included (cheap, keeps the check honest).
- Keep a Track B branch fresh by *merging* `main` in, never rebasing a pushed branch (rebase forces `--force`, destructive across machines).
- **Never `git merge -X theirs` / `-X ours` onto the trunk.** It auto-resolves every conflict silently in one side's favour — the fast way to *delete a teammate's just-pushed work with no conflict marker to warn you*. Resolve conflicts by reading both sides. (This is a real incident class: routes and features lost to a last-writer-wins merge.)
- **Nothing sensitive in commit messages.** No secrets/tokens, and **no raw model/investigation prompts or tool output** pasted into a subject or body — they leak internal detail into a permanent public record. Write a human summary of *what and why*.

### Hot files & merges — the collision discipline

The dominant multi-agent failure mode is not bad code, it's **silent merge loss** in files many people touch — a route registry (`routes/*`), a menu/nav manifest, a service-provider or DI container, a shared translations/config file. Two pushes race and the later one quietly wins; a route, an import, or a whole feature vanishes with no conflict marker. Defences, in order of leverage:

1. **Additive-only in contended files.** In a known hot file, *append* your entry (a new route line, a new menu item); don't reorder, reflow, or re-sort the existing entries in the same commit. Reordering turns a clean append into a whole-file conflict and invites a bad resolution.
2. **The 10-second post-merge survival check.** After any `merge`/`pull`/fast-forward that touched a contended file, **grep that your own change is still there** (`git grep '<my-route-or-import>' -- <hot-file>`). Losses are silent; this is the only thing that catches them at the source. Make it a reflex, not an afterthought.
3. **Ban the silent auto-resolvers** — see the `-X theirs/ours` rule above.
4. **Shrink the battleground (the durable fix).** When a file becomes a repeated collision site, split it per-feature (per-feature route files behind a loader; per-module menu fragments) so two people rarely edit the same file. This is an architecture task — raise it, don't just keep merging the monolith. Known hot files here: `src/Records/Envelope.php` (every record type maps through it), `config/daywatch.php` (the option table), and `composer.json` (the constraints every tooling change wants to touch).

> A `merge=union` `.gitattributes` on an append-only registry is a *possible* mitigation, but it interleaves both sides' lines and can't help with same-line edits — decide it per-project and only for genuinely additive files; it is never a substitute for rules 1–2.

### Commit + push cadence — be proactive

Don't wait to be asked. **Commit-and-push when:** a change reaches a coherent shippable state (Track A) or a logical waypoint (Track B); a doc/investigation chunk is worth preserving; at session end (at minimum, push the dated log + your `status/<you>.md`). **Before pushing, verify:** deployable/committable state; build + relevant tests pass; nothing accidentally staged (`.env`, secrets, binaries). Match the existing commit-message style; subject is a short imperative; body carries the *why*.

> **A real commit subject is not cosmetic — it's load-bearing.** Terse subjects (`push`, `wip`, `fix`) break more than readability: they break the automated state that reads your commits. A meaningful subject + updating your `status/<you>.md` **in the same push** is what lets the queue reconcile itself; skip it and the lead pays for it in manual catch-up (and stale status files cause duplicate builds). One logical change per commit.

## Session management (the shared memory)

State is **partitioned by author so writes never collide; everyone reads everything.** **`<author>` is always the person** (from git identity), whichever harness drove the work — agents stamp their harness *inside* the files: the `**Agent:**` field in dated logs and a per-agent `## <harness>` section in `status/<author>.md`, each agent editing only its own section (concurrent agents of one person → Track A2 worktrees, so disjoint sections merge cleanly). See [`docs/sessions/README.md`](docs/sessions/README.md).

- `SHARED.md` — thin cross-cutting state (≤ ~50 lines, append-mostly).
- `status/<author>.md` — your live WIP; you only ever write your own. Fixed schema: `## In flight` (≤2) · `## Next up` (ordered backlog) · `## Open follow-ups` · `## Shipped` (evidenced). **Keep the whole file ≤ ~80 lines** — `## Shipped` is a *rolling* ticker (the ~8 most-recent lands, or ~last 14 days), one line each with evidence; delete older entries (the dated logs are the permanent archive — don't re-list a land that's already in one) and move merged items out of `## In flight` promptly. An oversized status file taxes every session that loads it; the SessionStart tick warns you when your own file is over the cap. Trim it yourself (write-partition: only you edit your file) — if a teammate's is bloated, send a note, don't prune it for them.
- `notes/` — inter-author inbox; action then archive.
- `<author>/YYYY-MM-DD-*.md` — dated logs (full archive).

**Your backlog is `## Next up`** in your own status file — an ordered list, top = do-next; the single deterministic answer to "what should I work on next". Work gets there by you adding it, or by a teammate/lead dropping a note addressed to you (they can't edit your status file — that's the write-partition rule) which you triage into `## Next up`. This is what lets the lead reconcile the whole team's queue without asking anyone.

**Open every session with a short briefing** (state / priorities / in-flight / next-up / notes-for-you), then address the request. **Update incrementally** (after each commit, append to the log; capture findings as you find them). **Document the why and what you tested**, not just the what.

**Never assert project state from memory — verify it against `origin` first.** "I finished that PR earlier" / "that's already merged" / "the branch is still open" are exactly the claims that turn out false (the agent's mental model drifts from git within a session). Before you state that something is shipped/merged/open/in-flight — or act on it — check reality: `git fetch`, then feature-presence on `origin/main` and `gh pr view`. **"Shipped" means present on `origin/main`, verified — not "a doc (or I) said so."** This is the `status-reconcile` skill; run it, don't paraphrase it. A finished feature was once silently lost precisely because everyone trusted a doc no one checked against git.

> **These processes are also executable skills.** `session-docs` (this whole discipline + the exact doc-publish sequence) and `status-reconcile` ("docs are claims, git is truth" before you write "shipped") live in `.claude/skills/` so a lighter/unattended model follows them deterministically. This manual is the policy; the skills are its runnable rendering — **if a skill ever disagrees with this file, this file wins and the skill gets fixed.** Regression-test skill edits with `skill-bench` before committing them.

## Board coordination (live presence + claims)

The durable memory is git (above); the **live** layer is the team's AID Board — its task queue and its pulse. Git state reaches the board on a 15-minute sync; MCP calls reach it in under a second. Agents coordinate through the board to stop duplicated work *before* it starts:

- **Session start:** check `list-tasks` (especially `mine`) — what you already hold, what's freshly queued, and whether the thing you're about to do is already claimed. If it's claimed, don't start a second copy: comment, or pick something else.
- **Before working:** `claim-task` on the task you hold (file one first if it doesn't exist). A claim is the board's single source of "someone is on this" — humans and other agents (the healer included) all honor it.
- **While working:** `post-activity` beats (starting / working / blocked / done) — the pulse is how the operator and other agents see you live.
- **Finishing:** `advance-task` with evidence (sha, run id, URL) — the review stage requires it.
- **The recipe:** MCP at your board's `/mcp` endpoint, bearer token from your secrets vault (per-operator install). Core calls: `list-tasks`, `get-task`, `claim-task`, `comment-task`, `advance-task`, `post-activity`.

## Harness & permissions — so automation doesn't silently stall

The session-briefing and post-commit hooks are the only *truly* automatic parts. Everything else — writing logs, updating status, archiving notes — is **the agent following this manual, not a hook.** On Claude Code it *feels* automatic only because the `SessionStart` hook re-injects this manual every session and re-primes the behaviour. On a harness with no session-start hook (Codex/Cursor/Gemini/Replit), nothing re-primes it, so it does not happen unless you run the manual bootstrap in the entrypoint. **If a behaviour depends on the model re-reading an instruction, it must be re-primed every session** — don't assume cross-harness parity for anything that isn't an actual hook.

**Kimi Code is wired differently.** It only appends hook output to the model's context on `UserPromptSubmit` — `SessionStart` is observation-only — so the session-log reminder lives on `UserPromptSubmit` and the commit safety net on `SessionEnd`; don't move the nudge to SessionStart, it will silently stop reaching the model. The Kimi hooks are a **per-operator** install (`~/.kimi-code/hooks/` + the `[[hooks]]` blocks in `~/.kimi-code/config.toml`), are project-agnostic (they detect the `docs/sessions/status` or `sessions/status` convention per project), and are only a safety net — the agent still writes the dated log; the hooks guarantee the prompt to log and the commit/push of it. **They self-provision:** adopted projects carry the scripts + `kimi-install.sh` in `.claude/hooks/`, and the AGENTS.md bootstrap runs it at session start — on a Kimi machine it installs/updates the operator install idempotently (backing up `config.toml`, never touching unrelated blocks), and the agent tells the operator when a fresh session is needed to activate.

**Unattended / remote runs (phone, background, cron).** In the default permission mode an unanswered permission prompt **blocks forever** — no timeout, no auto-deny. A loaded allow-list isn't enough: the first tool you *haven't* pre-approved (often an MCP call) hangs the whole session, and from a phone it just looks "stuck." Applied to logging: if `Write`/`Edit` aren't approved, every log-write throws a prompt, so logs "don't auto-write."

- Set a non-blocking mode in your **user-level** `~/.claude/settings.json` (not the repo's committed `settings.json`, and never commit `bypassPermissions`): `permissions.defaultMode: "dontAsk"` (auto-denies un-allow-listed tools — recommended) or `"bypassPermissions"` (auto-approves everything, including prod SSH — only for trusted boxes). Read at session start, so **restart the session** after changing it.
- Allow-list the routine file ops (`Read`, `Edit`, `Write`, `Bash`) so logging never prompts.
- No setting suppresses the agent's *own* `AskUserQuestion` / plan-mode pauses. For a genuinely hands-off run, instruct it up front: *"don't ask — make reasonable assumptions and proceed."*

## Model selection & cost

At team scale the bill is real (an unmanaged setup projects into five figures a month, and prices rise), so match the model to the job instead of everyone defaulting to the most expensive one:

- **Architecture / planning / investigation / adversarial review → the strongest reasoning model** (e.g. Fable/Opus). This is where a better model pays for itself. The emerging consensus pattern: **plan with the strong model, then hand the defined implementation to a cheaper/faster one.**
- **Defined implementation against a clear spec → a mid/fast model** (Opus/Codex/Sonnet) — the plan already did the hard thinking.
- **Mechanical / skill-driven checks (lint, reconcile, benchmark, log) → the cheapest capable model** (Haiku). The process skills are written to survive a light model *by design* — that's what `skill-bench` proves.
- **Pin sub-agent models explicitly.** Sub-agents silently running on a bigger model than intended is a real cost leak — set the `model:` field in each agent definition; don't leave it to inherit.
- **Watch per-person token burn**, and treat `Overloaded` / rate-limit errors as a signal to down-shift the model or batch work, not to retry harder. No project-specific overrides; the code-reviewer sub-agent is pinned to opus.

## Environments & dependencies — pin them

Drift between where you develop, test, and deploy produces bugs that only appear in one place and waste a session to chase. Hold one story:

- **One runtime-version story across dev / CI / prod / any secondary console (Replit etc.).** A language/runtime version that differs in one environment (e.g. prod on 8.3 while a sandbox floated to 8.4) is a latent incident — align them and say which is canonical.
- **Pin dependency versions** (lockfile committed, no floating majors on anything load-bearing). "We'll pin once things slow down" never arrives — an AI-led pace doesn't slow down.
- **Verify the harness's own environment assumptions travel** (`.gitignore` actually excludes what you think; the harness dir isn't ignored; asset/upload dirs aren't leaking into commits).

## Tokens & secrets — hygiene

- **Service/machine tokens are scoped and per-service, never a shared personal PAT.** A single highly-privileged token shared across people/instances becomes one person's de-facto identity and an unscoped blast radius — give each service/instance its own least-privilege token, stored in the team secret manager.
- Never commit secrets; never paste raw prompts/tool output into commit messages (see git rules). The package holds no secrets of its own — `DAYWATCH_TOKEN` / `DAYWATCH_BASE_URL` live in the *host app's* `.env`, never in this repo; tests use dummy tokens and a stub TCP server.

## Validation — test as the user, on the real surface (the sign-off)

Tooling that reports "working" — a green suite, clean logs, an HTTP 200 — is **necessary but not sufficient** for anything a user sees. A client-rendered page (Alpine/React/HTMX/…) can serve a healthy-looking 200 *shell* while a broken client fetch leaves it empty; a report can render cleanly with stale or wrong-tenant data. The recurring, expensive failure class is *"the tooling signed it off, and one glance at the live page disproves it."* Tests can't see it; only looking can.

So for anything user-facing, the order of proof is:

1. **Drive the real surface, then _read the artifact_.** This package renders no pages — its operator surface is the daemon itself. When a change touches daemon/ingest/socket behaviour, run `daywatch:agent` against the suite's stub TCP server (or a throwaway testbench host) plus `daywatch:status --json`, and **look at the output** — assert a real fact ("the auth probe reports accepted", "the records-sent counter moves"), not merely "it booted". The "hostile host" suite is the automated version of this.
2. **Tests remain necessary** (they stop regressions travelling) but are **not** the sign-off for a rendered surface.
3. **"Verified" in a session log means: which command, which surface, what you saw** — not "tests green".

Reads anywhere; writes only against a local stub ingest or a throwaway Daywatch dev stack, torn down and confirmed cleaned up afterwards. Pure library changes with no daemon/socket impact: this step is N/A.

## Testing requirements

- **Every change is programmatically tested** — new or updated test, run it. Tests are part of *done*.
- Critical-core: human-owned tests, happy + failure + edge, real round-trip where it matters.
- Disposable surface: lighter bar, the daily E2E is the net.
- *"Tested against what?"* (env, real vs fixtures, suite green) goes in the log.
- Run a minimal filter as you go; full suite before risky merges. Lint/format gate before finalising.
- **Daily E2E** = the scheduled `tests.yml` matrix (PHP 8.3/8.4 × Laravel 11/12/13, cron `17 6 * * *`): on failure, surface in the briefing and ask before triaging.
- **Every red E2E run has a named owner, claimed the same day.** A regression-catcher nobody reads is *worse* than none — it manufactures false confidence, and the channel gets muted within the hour. Rule: the most-recent red run is claimed in-channel (in `SHARED.md` or the alerts channel) by end of day, or the alert is worthless. Send E2E failures to a **dedicated alerts channel**, not a busy work channel, so they're not drowned and muted. A repeatedly-firing failure with no owner is a process defect to fix, not noise to tune out.
- **Metrics as regression tests.** None wired yet — candidate: the daemon's own STATS counters (records sent / send failures / retries) exercised on the same schedule (hourly where the metric earns it). A regression caused by a change gets an auto-remediation agent as first responder, and a failed auto-fix still leaves a red run with a named same-day owner.
- **Synthetic QA.** The "hostile host" suite already plays the synthetic adversarial host (dead daemon, wrong token, oversized payloads); nothing beyond it is stood up yet.

Commands:
```bash
composer install && composer test   # Pest suite, self-contained (filter: vendor/bin/pest --filter=...)
vendor/bin/pint --test              # format/lint gate (composer format to fix)
composer test                       # the suite IS the end-to-end check (incl. hostile-host dead-daemon paths)
```

## Key gotchas — read before changing

- Dev tooling needs PHP ≥ 8.3 (every Pest 4.x does) while hosts stay on `^8.2` — don't "fix" the `php` constraint, and never tighten `illuminate/support ^11|^12|^13`: those two constraints ARE the host-compat contract.
- Never add `react/http` — it pins `psr/http-message ^1.0`, which conflicts with a Laravel 13 host's `^2.0`. The daemon POSTs raw HTTP/1.1 over `react/socket` for exactly this reason; the `react/*` pins stay tilde-locked.
- `ext-zlib` is a `suggest`, not a `require` — `gzencode` stays behind `function_exists` guards so a zlib-less host pauses upload (drop + log) instead of fatalling.
- Record DTOs declare an `Envelope` and emit only their own fields — `EnvelopeTest` fails the build if one re-inlines the envelope.
- Sensor tests assert exact payload field names — those names ARE the wire contract; renaming one is a cross-repo change (docs-first via the `daywatch-payloads` skill), not a package-local refactor.
- `CLAUDE.md` is the editing source of the engineering guide — re-copy it to `AGENTS.md` after edits (the two are kept identical).
