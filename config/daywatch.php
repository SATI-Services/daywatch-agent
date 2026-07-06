<?php

// Daywatch agent configuration. The full contract lives in daywatch-mcp/docs/agent-protocol.md §7.
// Publish into your app with:  php artisan vendor:publish --tag=daywatch-config

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
    | Filtering (M4) — the privacy boundary runs in-app, before buffering.
    |--------------------------------------------------------------------------
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

        // Live console (only when daywatch:agent is attached to a TTY): repaint the
        // dashboard every N seconds, keeping the last M log lines on screen. Set the
        // refresh to 0 — or pass --plain — to fall back to scrolling plain-line
        // logging + the periodic stats line (the supervisor/log-file behaviour).
        'console_refresh' => env('DAYWATCH_DAEMON_CONSOLE_REFRESH', 3),
        'console_lines' => env('DAYWATCH_DAEMON_CONSOLE_LINES', 10),
    ],

];
