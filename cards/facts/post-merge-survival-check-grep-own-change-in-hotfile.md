---
title: After any merge, immediately grep your own change back into contended files
tags: [git, discipline, collision, reflex]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: 20dd42e0f 2026-11-06
---
After any `merge`/`pull`/fast-forward that touched a contended file, **grep your own change is still there** (`git grep '<my-route-or-import>' -- <hot-file>`). Losses are silent; this post-merge survival check is the only thing that catches them at the source. Make it a reflex, not an afterthought.

**Why:** Silent merge-loss is the dominant multi-agent failure mode. The 10-second post-merge grep is the only practical early detection.

---
