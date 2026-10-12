# 2026-10-12 — tests: request-capture middleware + facade un-crashability

**Author:** sts-ryan-holton
**Agent:** kimi (unreported)

## Context (from Ryan)

`1.2.0` was tagged and pushed earlier today; the ask was whether there were
more tests worth pushing. Audited the suite for protocol-critical paths with
no or thin coverage rather than adding noise.

## Gaps found (and closed)

1. **`Http/Middleware/CaptureRequest` had zero tests** — the request sensor's
   entry point. New `tests/Http/CaptureRequestTest.php`:
   - starts the request execution (source `request`, bootstrap stage closed,
     stage µs ≥ 0) and hands the request untouched to the next middleware;
   - a sensor that lets an exception escape never blocks the request — the
     cardinal rule at the HTTP boundary (`$next` still runs, response returned).
   - Note: the `LARAVEL_START`-undefined branch is not exercisable in-process
     (constants can't be undefined); documented in the file.

2. **The kernel prepend was unasserted** — `SensorManagerEnabledTest` diffed
   event listeners only; the other half of `attachRequestSensor` is
   `prependMiddleware(CaptureRequest::class)`, and without it no request
   execution ever starts. Added two tests: a fresh `register()` prepends it
   (boot's copy stripped first — the Foundation kernel dedupes prepends, and
   Testbench's kernel has no accessor, so the list is read/reset via
   reflection, the suite's existing idiom), and a disabled RequestSensor
   prepends nothing.

3. **`src/Daywatch.php` (the facade root) was only happy-path tested** — its
   whole reason for existing is "every method swallows failures and returns
   `$this`" as the second line of defence behind Core's own guards. New
   `tests/Unit/DaywatchManagerTest.php`:
   - all eight methods (`user/sample/dontSample/report/ignore/pause/resume/
     digest`) never throw and stay fluent against an exploding Core subclass;
   - the provider's guarded factory resolves a disabled no-op Daywatch when
     Core can't be built (`app()->bind(Core::class, fn () => throw ...)`).

Already covered (checked before writing): queue-hop propagation adopt/write
(CoreTest), `reset()` worker boundary incl. forced-off sampling, throwing and
recursing user resolvers (UserResolutionTest), buffer byte/count bounds +
ring-drop (RecordsBufferTest), about section + facade happy path (SmokeTest).

## Tests

367 passing (361 baseline + 6 new, 1150 assertions), pint clean (141 files).
Baseline verified by stashing all changes incl. untracked and re-running. (The
361 baseline includes the `sample_rate` wire stamping — `b863ab9` + log
`7b6840c` — which a concurrent session committed to this checkout mid-audit;
all runs above were against the post-stamping tree.)

## Files touched

- `tests/Http/CaptureRequestTest.php` (new)
- `tests/Unit/DaywatchManagerTest.php` (new)
- `tests/SensorManagerEnabledTest.php` (+2 tests)
- this log + `docs/sessions/status/ryan.md` + `docs/sessions/SHARED.md`

No version bump — test-only change, no behaviour change.
