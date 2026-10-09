# 2026-10-09 — audit: does the agent issue its own user SELECT queries?

**Author:** sts-ryan-holton
**Agent:** kimi (unreported)

## Question (from Ryan)

Does the daywatch-agent package perform any user-query SELECT statements
itself, or is it purely observational?

## Answer: observing only — with one framework-lazy-load nuance

Read-only audit of `src/`, no code changed.

- Grep over `src/` for `DB::`, `Auth::`, `->select(`, `::query()`, `User::`,
  `->find(`, `->first(`, `->where(` — **zero query-issuing hits**. The only
  match is `src/Records/QueryRecord.php:31`, the literal record-type label
  `'query'` in the wire envelope.
- All 14 sensors are event listeners. `QuerySensor::handle(QueryExecuted
  $event)` (`src/Sensors/QuerySensor.php:38`) only *records* SQL the host app
  already executed (raw SQL, bindings never substituted); it never runs a
  query.
- User resolution is a read of the already-authenticated user:
  `auth()->user()` in `src/Sensors/UserSensor.php:105` (the `user` record) and
  `src/Core.php:424` (per-record `user` field). No hand-written query anywhere.

**Nuance worth knowing:** `auth()->user()` delegates to Laravel's session
guard, which lazily resolves. If a visitor is logged in (user id in session)
but *nothing earlier in the request* resolved the user (e.g. a public route
with no auth middleware), the agent's `auth()->user()` call is the first
resolution → the **framework** runs its own `retrieveById()` → one
`select ... from users where id = ?`. The agent never writes that query, but
it can be the trigger. On authenticated routes the `Authenticate` middleware
resolves the user first, so the guard read is cached and costs zero queries.

Related design notes confirmed during the audit:

- `Core::resolveUser()` (`src/Core.php:369`) is memoised + re-entrancy
  latched (`resolvingUser`) precisely because resolution may touch the
  DB/logging — a resolver or guard that queries can't recurse back through a
  sensor.
- If the guard's lazy SELECT does fire, `QuerySensor` observes and records it
  like any other host query.
- `UserSensor::attr()` reads `name`/`username` via `isset($user->{$key})` —
  attribute reads on an already-loaded model, no query (a relation-backed
  accessor could lazy-load; host-side edge case).
- A host-supplied `Daywatch::user(fn ($user) => ...)` resolver is host code;
  if it queries, that's the host's query, still guarded by the latch.

## Files touched

- none (code); this log + `docs/sessions/status/ryan.md`
