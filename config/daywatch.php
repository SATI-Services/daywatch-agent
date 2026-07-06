<?php

// Daywatch agent configuration. The full contract lives in services/daywatch-mcp/docs/agent-protocol.md §7.
// Publish into your app with:  php artisan vendor:publish --tag=daywatch-config
// Every key is env-backed, so you rarely need to publish this file to tune it.

return [

    /*
    |--------------------------------------------------------------------------
    | Core
    |--------------------------------------------------------------------------
    */

    // Master switch. When false, no sensors attach and nothing is buffered.
    'enabled' => env('DAYWATCH_ENABLED', true),

    // Per-environment ingest token (dw_...). Hashed into the local socket frame
    // and sent as the Bearer token to the central ingest.
    'token' => env('DAYWATCH_TOKEN'),

    // Base URL of your Daywatch instance. REQUIRED for transmission.
    'base_url' => env('DAYWATCH_BASE_URL'),

    // Release marker attached to every record as `deploy` (≤255 B).
    'deployment' => env('DAYWATCH_DEPLOY'),

    // Hostname label attached to every record as `server` (≤255 B).
    'server' => env('DAYWATCH_SERVER', gethostname() ?: null),

    // Inline app-file source context into exception traces (docs §2, `exception`).
    'capture_exception_source_code' => env('DAYWATCH_CAPTURE_EXCEPTION_SOURCE_CODE', true),

    /*
    |--------------------------------------------------------------------------
    | Head sampling (decided once per execution — services/daywatch-mcp/docs/agent-protocol.md §3)
    |--------------------------------------------------------------------------
    */

    'sampling' => [
        'requests' => env('DAYWATCH_REQUEST_SAMPLE_RATE', 1.0),
        'commands' => env('DAYWATCH_COMMAND_SAMPLE_RATE', 1.0),
        'scheduled_tasks' => env('DAYWATCH_SCHEDULED_TASK_SAMPLE_RATE', 1.0),
        // Re-rolled by Daywatch::report() so errors escape sampled-out traces.
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

    // TODO(M4): arrive with header/payload capture. Default to the Nightwatch
    // redaction lists (authorization/cookie headers, password/token fields).
    'redact_headers' => [
        // e.g. 'authorization', 'cookie', 'set-cookie', 'x-xsrf-token'
    ],
    'redact_payload_fields' => [
        // e.g. 'password', 'password_confirmation', 'token'
    ],

    /*
    |--------------------------------------------------------------------------
    | Local ingest (app → daemon, framed TCP — services/daywatch-mcp/docs/agent-protocol.md §4)
    |--------------------------------------------------------------------------
    */

    'ingest' => [
        // Daemon address the app digests to / the daemon listens on.
        // Docker deployments use 0.0.0.0:2408.
        'uri' => env('DAYWATCH_INGEST_URI', '127.0.0.1:2408'),
        // Socket WRITE / ack-read timeout, in seconds (blocking, per digest).
        'timeout' => env('DAYWATCH_INGEST_TIMEOUT', 0.5),
        // Socket CONNECT timeout, in seconds (blocking, per digest).
        'connection_timeout' => env('DAYWATCH_INGEST_CONNECTION_TIMEOUT', 0.5),
        // Records BUFFER SIZE: max records held before an auto-digest is forced
        // mid-execution (the per-execution buffer cap; ring-drops when unsampled).
        'event_buffer' => env('DAYWATCH_INGEST_EVENT_BUFFER', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent daemon (daywatch:agent — batches digests and POSTs to /api/ingest)
    |--------------------------------------------------------------------------
    */

    'agent' => [
        // Flush the batch to the central ingest once it reaches this many bytes.
        'flush_bytes' => env('DAYWATCH_AGENT_FLUSH_BYTES', 6_000_000),
        // ...or this many seconds after the first byte was buffered, whichever first.
        'flush_interval' => env('DAYWATCH_AGENT_FLUSH_INTERVAL', 10),
        // Max concurrent in-flight POSTs to the central ingest; excess is dropped.
        'max_concurrent_requests' => env('DAYWATCH_AGENT_MAX_CONCURRENT_REQUESTS', 5),
        // Connect / overall request timeout (seconds) for the outbound ingest POST.
        'connect_timeout' => env('DAYWATCH_AGENT_CONNECT_TIMEOUT', 5),
        'request_timeout' => env('DAYWATCH_AGENT_REQUEST_TIMEOUT', 10),
        // Log level for the daywatch:agent daemon's own diagnostics.
        'log_level' => env('DAYWATCH_AGENT_LOG_LEVEL', 'error'),
    ],

    'daemon' => [
        // Seconds between the daywatch:agent daemon's one-line ingest-stats log
        // on stdout (buffered/sent counters — docs §5). 0 disables the line.
        'stats_interval' => env('DAYWATCH_DAEMON_STATS_INTERVAL', 60),
    ],

];
