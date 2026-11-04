---
title: HttpResponse.json() decodes body tolerantly, returns [] on any failure
tags: [daemon, http, transport]
status: verified 2026-11-04 auto
source: [src/Daemon/HttpResponse.php]
as_of: 23d988245 2026-11-04
---
HttpResponse.json() decodes the body with JSON_THROW_ON_ERROR and returns [] if the body is not valid JSON or not an array.

**Why:** The ingest dispatcher must reason over responses from the server without crashing on malformed bodies.
