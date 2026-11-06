---
title: Session docs filed under the person; harness + model stamped inside the file
tags: [sessions, identity, harness]
status: verified 2026-11-06 auto
source: [.claude/harness/instructions/INSTRUCTIONS.md]
as_of: 20dd42e0f 2026-11-06
---
Session docs, status files and dated logs are filed under the **operator's** identity (`status/<person>.md`, `<person>/<date>.md`), whichever AI harness drove the work. The harness name is a stamp **inside** the file (`**Agent:** <harness> (<model>)`), never the directory name. That stamp names the harness **and the model** — never leave the model implied by the harness. A session that **switches model mid-flight** stamps the whole sequence (`**Agent:** kimi (a → b)`) and says which part of the work each model did.

**Why:** The work belongs to the person, not the harness. One harness runs several models; which model drove a session changes how its work should be read.

---
