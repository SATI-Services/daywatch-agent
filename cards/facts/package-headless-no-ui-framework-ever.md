---
title: Keep the package headless: no Livewire/Tailwind/UI dependencies ever
tags: [dependencies, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
The package stays **headless**: no Livewire, Tailwind, or UI framework dependencies ever. AGENTS.md lists this under pinned constraints ("don't drift"), alongside `illuminate/support ^11|^12|^13` and PHP `^8.2`. The package is sensors into a bounded buffer, a socket client, and a ReactPHP daemon; it renders no surface.

**Why:** AGENTS.md records it as a pinned package constraint, not a per-feature choice.
