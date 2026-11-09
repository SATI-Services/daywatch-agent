---
title: Mail record to, cc, bcc, attachments are integer counts only
tags: [records, wire-contract, privacy]
status: verified 2026-11-09 auto
source: [src/Records/MailRecord.php]
as_of: a6c4bee62 2026-11-08
---
MailRecord `to`, `cc`, `bcc`, and `attachments` fields carry integer COUNTS only — recipient addresses are never transmitted.

**Why:** Protects recipient privacy while preserving observability of mail volume and composition.
