---
title: Host-compat constraints are php ^8.2 and illuminate/support ^11|^12|^13
tags: [constraints, host-compatibility, requirements]
status: verified 2026-09-09 auto
source: [CLAUDE.md, docs/WORKFLOW.md]
as_of: 16a6c506a 2026-09-06
---
The package requires `php ^8.2` and `illuminate/support ^11|^12|^13`. These constraints ARE the host-compat contract and must never be tightened for tooling or any other reason. Dev tooling (Pest, Testbench) requires PHP ≥ 8.3, but this is a dev-only requirement, never shipped to hosts.

**Why:** Hosts run on PHP 8.2; dev/CI can require 8.3+ for test infrastructure. Tightening the host constraint breaks consumers unnecessarily.
