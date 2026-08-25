<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Core
    |--------------------------------------------------------------------------
    */

    'enabled' => env('DAYWATCH_ENABLED', true),
    'token' => env('DAYWATCH_TOKEN'),
    'base_url' => env('DAYWATCH_BASE_URL'),
    'deployment' => env('DAYWATCH_DEPLOY'),
    'server' => env('DAYWATCH_SERVER', gethostname() ?: null),
    'capture_exception_source_code' => env('DAYWATCH_CAPTURE_EXCEPTION_SOURCE_CODE', true),

    /*
    |--------------------------------------------------------------------------
    | Head sampling (decided once per execution — daywatch-mcp/docs/agent-protocol.md §3)
    |--------------------------------------------------------------------------
    */

    'sampling' => [
        'requests' => env('DAYWATCH_REQUEST_SAMPLE_RATE', 1.0),
        'commands' => env('DAYWATCH_COMMAND_SAMPLE_RATE', 1.0),
        'scheduled_tasks' => env('DAYWATCH_SCHEDULED_TASK_SAMPLE_RATE', 1.0),
        'exceptions' => env('DAYWATCH_EXCEPTION_SAMPLE_RATE', 1.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Filtering + redaction (RESERVED — M4)
    |--------------------------------------------------------------------------
    |
    | Declared here because they are part of the shared option table
    | (agent-protocol.md §7) and the privacy boundary is deliberately in-app, before
    | buffering. NOT YET READ by the collector: setting them today changes
    | nothing. They are listed so the wire contract and this file stay in step —
    | do not treat them as working switches until the sensors consult them.
    |
    */

    'filtering' => [
        'ignore_queries' => env('DAYWATCH_IGNORE_QUERIES', false),
        'ignore_cache_events' => env('DAYWATCH_IGNORE_CACHE_EVENTS', false),
        'ignore_outgoing_requests' => env('DAYWATCH_IGNORE_OUTGOING_REQUESTS', false),
        'log_level' => env('DAYWATCH_LOG_LEVEL', 'debug'),
    ],

    'redact_headers' => [
        // e.g. 'authorization', 'cookie', 'set-cookie', 'x-xsrf-token'
    ],

    'redact_payload_fields' => [
        // e.g. 'password', 'password_confirmation', 'token'
    ],

    /*
    |--------------------------------------------------------------------------
    | Local ingest (app → daemon, framed TCP — daywatch-mcp/docs/agent-protocol.md §4)
    |--------------------------------------------------------------------------
    */

    'ingest' => [
        'uri' => env('DAYWATCH_INGEST_URI', '127.0.0.1:2408'),
        'timeout' => env('DAYWATCH_INGEST_TIMEOUT', 0.5),
        'connection_timeout' => env('DAYWATCH_INGEST_CONNECTION_TIMEOUT', 0.5),
        'event_buffer' => env('DAYWATCH_INGEST_EVENT_BUFFER', 500),

        // Second bound on the in-process buffer, in bytes (0 disables). Field caps
        // let one record carry megabytes, so a record COUNT alone is not a memory
        // bound — this is what keeps the host's footprint predictable. Sized just
        // under the daemon's 6 MB flush threshold so one digest ≈ one batch.
        'buffer_bytes' => env('DAYWATCH_INGEST_BUFFER_BYTES', 5_000_000),

        // Circuit breaker: after a failed digest, skip further socket attempts for
        // this many seconds (0 disables). Bounds host-request cost when the daemon
        // is persistently down/hung; a healthy digest closes it immediately.
        'failure_cooldown' => env('DAYWATCH_INGEST_FAILURE_COOLDOWN', 2.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent daemon (daywatch:agent — batches digests and POSTs to /api/ingest)
    |--------------------------------------------------------------------------
    */

    'agent' => [
        'flush_bytes' => env('DAYWATCH_AGENT_FLUSH_BYTES', 6_000_000),
        'flush_interval' => env('DAYWATCH_AGENT_FLUSH_INTERVAL', 10),
        'max_concurrent_requests' => env('DAYWATCH_AGENT_MAX_CONCURRENT_REQUESTS', 5),
        'connect_timeout' => env('DAYWATCH_AGENT_CONNECT_TIMEOUT', 5),
        'request_timeout' => env('DAYWATCH_AGENT_REQUEST_TIMEOUT', 10),
        'log_level' => env('DAYWATCH_AGENT_LOG_LEVEL', 'error'),
    ],

    'daemon' => [
        'stats_interval' => env('DAYWATCH_DAEMON_STATS_INTERVAL', 60),

        // Startup authentication check: as daywatch:agent boots it resolves its
        // token against {base_url}/api/ingest/tenancy, so a wrong DAYWATCH_TOKEN or
        // an ingest that isn't up is reported immediately instead of surfacing later
        // as silently dropped batches. Diagnostic only — a failed check never stops
        // the daemon. Disable with false or the --no-auth-check flag.
        'auth_check' => env('DAYWATCH_DAEMON_AUTH_CHECK', true),

        // Live console (only when daywatch:agent is attached to a TTY): repaint the
        // dashboard every N seconds, keeping the last M log lines on screen. Set the
        // refresh to 0 — or pass --plain — to fall back to scrolling plain-line
        // logging + the periodic stats line (the supervisor/log-file behaviour).
        'console_refresh' => env('DAYWATCH_DAEMON_CONSOLE_REFRESH', 3),
        'console_lines' => env('DAYWATCH_DAEMON_CONSOLE_LINES', 10),
    ],

];
