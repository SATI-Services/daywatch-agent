---
title: Dev tooling needs PHP ≥8.3 (every Pest 4.x does); consumers stay on php ^8.2
tags: [php, dependencies, dev]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: 41d44b355 2026-10-31
---
Dev tooling needs **PHP ≥ 8.3** (every Pest 4.x does) while consumers keep `php ^8.2` — dev deps never ship to hosts.

**Why:** Pest 4.x requires PHP 8.3; `php ^8.2` is part of the package's host-compatibility contract in `require` and is never tightened for tooling's sake.
