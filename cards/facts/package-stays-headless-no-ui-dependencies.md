---
title: Package is headless; no Livewire, Tailwind, or UI dependencies ever
tags: [architecture, dependencies, scope]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
The package stays headless: no Livewire, Tailwind, or UI dependencies. It is a telemetry collector, not a UI layer.

**Why:** UI dependencies would unnecessarily couple the package to frontend frameworks, increasing bloat and compatibility burden for hosts that don't use those stacks.
