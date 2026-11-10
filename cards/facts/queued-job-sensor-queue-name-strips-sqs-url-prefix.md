---
title: QueuedJobSensor strips SQS URL prefix from queue name, keeps trailing segment
tags: [sensor, queued-job, queue, sqs]
status: verified 2026-11-10 auto
source: [src/Sensors/QueuedJobSensor.php]
as_of: 273b424ce 2026-11-10
---
`QueuedJobSensor::queue()` takes everything after the last `/` in `$event->queue` and keeps only that segment. It returns `'default'` when the queue value is missing, not a string, or empty; when the extracted segment is itself empty (e.g. a trailing slash); and on any `Throwable`.

**Why:** SQS queue names arrive as full URLs (`https://sqs.region.amazonaws.com/account/queue-name`); extracting the queue name only is more readable.
