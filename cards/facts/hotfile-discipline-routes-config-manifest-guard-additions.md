---
title: Hotfile additions — append only, never reorder existing entries in the same commit
tags: [git, discipline, collision]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: 20dd42e0f 2026-11-06
---
In a known hotfile (routes, config, service-provider DI, shared manifest), **append your entry only**; never reorder, reflow, or re-sort existing entries in the same commit. Reordering turns a clean append into a whole-file conflict and invites a bad resolution — silent merge loss.

**Why:** Multi-agent dominant failure mode is not bad code but silent merge loss in files many people touch. Two pushes race; the later one quietly wins. A route, an import, or a whole feature vanishes with no conflict marker to warn you.

---
