---
title: Every Daywatch facade method returns DaywatchManager, so calls chain
tags: [api, facade]
status: verified 2026-11-04 auto
source: [src/Facades/Daywatch.php]
as_of: 3b03a140f 2026-11-04
---
Each method the `Daywatch` facade declares returns `DaywatchManager`, not a value — so a facade call can be chained, e.g. `Daywatch::user(...)` followed by another manager call.

The facade class body defines only `getFacadeAccessor()`, which resolves `Daywatch\Agent\Daywatch`. The declared API surface lives in the class docblock's `@method static` tags.

**Why:** A caller has to know a facade call yields the manager rather than a result, or the fluent chain reads as a mistake.
