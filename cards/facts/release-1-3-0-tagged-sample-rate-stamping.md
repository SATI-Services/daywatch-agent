---
title: Release 1.3.0 tagged with sample_rate stamping commits
tags: [release, sampling, wire-contract]
status: verified 2026-10-28 auto
source: [docs/sessions/ryan/2026-10-12-release-1.3.0.md]
as_of: 397fecced 2026-10-27
---
Release 1.3.0 (tag `1.3.0`, commit `63da0de`) carries sample_rate stamping at digest time and π-correction for exception executions. Includes commits `b863ab9` (effective inclusion-probability stamping), `2e5e303` (exception-execution combined-rate correction), `d2b1043` (guard tests).

**Why:** Hosts running 1.2.0 sample data but never stamp `sample_rate` (stamping commits landed after that tag, in no tag). Server reads 1.0 and hides dashboard badges. Release is the missing piece to surface sampled-record metadata.
