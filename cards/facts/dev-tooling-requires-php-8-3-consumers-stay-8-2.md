---
title: Dev tooling needs PHP ≥8.3; consumers stay on php ^8.2
tags: [dependencies, dev-tooling, php, constraints]
status: verified 2026-09-09 auto
source: [CLAUDE.md, docs/WORKFLOW.md]
as_of: 16a6c506a 2026-09-06
---
Dev tooling (Pest 4.x, Testbench, Pint) requires PHP ≥ 8.3, while the package itself requires only `php ^8.2` for hosts. This is a dev-only constraint; never require 8.3+ from consumers.

**Why:** Hosts may run 8.2; dev infrastructure can be newer. The split allows development on modern tooling while keeping consumers on older supported versions.
