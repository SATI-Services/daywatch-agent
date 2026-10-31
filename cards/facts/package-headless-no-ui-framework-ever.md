---
title: Keep the package headless: no Livewire/Tailwind/UI dependencies ever
tags: [dependencies, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
The `daywatch/agent` package stays **headless**: no Livewire, Tailwind, or UI dependencies ever. It is a collector — sensors, socket client, ReactPHP daemon — with no rendered surface.

AGENTS.md lists this under **Pinned constraints (don't drift)**, alongside its host-compatibility constraints (`illuminate/support ^11|^12|^13`, PHP `^8.2`).
