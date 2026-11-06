---
title: illuminate/support ^11|^12|^13 and php ^8.2 never tightened in composer.json require
tags: [composer, host-compat, constraint]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: 20dd42e0f 2026-11-06
---
Only `illuminate/support ^11|^12|^13` and `php ^8.2` stay permissive in `require`; never tighten them. These two constraints ARE the host-compat contract. Dev tooling needs `php ≥8.3` (every Pest 4.x does) while consumers stay on `^8.2`.

**Why:** Tightening the host-compat constraint breaks backward compatibility for all installed hosts at their next `composer update`. The contract is public.

---
