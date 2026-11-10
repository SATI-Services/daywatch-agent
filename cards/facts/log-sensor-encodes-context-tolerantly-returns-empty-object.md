---
title: LogSensor returns {} for non-array or empty context, and on json_encode failure
tags: [sensors, serialization]
status: verified 2026-11-10 auto
source: [src/Sensors/LogSensor.php]
as_of: 72d7040e7 2026-11-09
---
`LogSensor::encodeContext()` returns `'{}'` when the context is not an array or is an empty array, and otherwise returns `json_encode($context, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE)`, falling back to `'{}'` if that encode throws.

**Why:** Context is user data and may be malformed; graceful fallback prevents sensor from breaking.
