---
title: Delete filtering block from published config when host apps upgrade past 1.0.x
tags: [config, cleanup]
status: open
owner: unassigned
since: 2026-10-12
source: [docs/sessions/status/ryan.md]
as_of: 6866d739e 2026-10-12
---
Host apps on 1.0.x that published configs: upgrade to 1.1.0 keeps legacy `filtering.*` keys honoured. Published config files' `filtering` block can be deleted when convenient — no action required on upgrade.
