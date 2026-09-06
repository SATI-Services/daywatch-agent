# Agent memory (persistent, per-agent)

Give a sub-agent (e.g. the reviewer) a durable memory so it gets smarter about *this* codebase over time instead of re-learning conventions every session. Set `memory: project` in the agent's frontmatter; the agent reads + writes files here.

## Layout

```
.claude/agent-memory/<agent-name>/
├── MEMORY.md                 ← one-line index; content lives in the linked files
├── convention_<topic>.md     ← an established pattern in this codebase
├── gotcha_<topic>.md         ← a trap that bit us, and how to avoid it
└── reference_<topic>.md      ← reusable scaffolding (test setups, query patterns)
```

## MEMORY.md is an index, not a dump

One line per memory, so the index stays cheap to load. Example:

```markdown
# Memory index
One line per memory. Content lives in the linked files, not here.

- [Writeback proxy controller shape](convention_writeback_proxy_controller.md) — allowlist + guardWrite; recurring missing-403 test
- [Unindexed created_at on big tables](gotcha_highvolume_search_sort.md) — resolve a date window to an id range first; flag any filter/sort on the column
- [Feature-test scaffolding](reference_readonly_controller_feature_tests.md) — canonical setup for read-only controller tests
```

## What's worth a memory

- A convention you had to *discover* from sibling files (not one that's obvious).
- A gotcha that already cost someone time (so the next agent doesn't repeat it).
- Reusable scaffolding the agent will want again.

Don't store what the code/CLAUDE.md already says, or anything that only mattered for one task. Update the matching file rather than adding duplicates; delete memories that turn out wrong.
