<?php

use Daywatch\Agent\Sensors;

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
    | Head sampling (decided once per execution — agent protocol §3)
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
    | Sensors (per-sensor switches, ignore lists and key grouping)
    |--------------------------------------------------------------------------
    |
    | One entry per sensor, keyed by class — Laravel Pulse's `recorders` idiom.
    | Every sensor supports `enabled` (listeners are not attached at all when
    | false, so a noisy source can be silenced without disabling Daywatch).
    | Some sensors add:
    |
    |   `ignore`  Subjects never recorded, as `Str::is()` patterns (`*`
    |             wildcard; a comma-separated env string or a config array).
    |             Applied to the RAW value before anything is buffered.
    |   `groups`  High-cardinality values collapsed to a label, Pulse-style:
    |             a map of regex pattern ⇒ replacement, first match wins,
    |             unmatched values recorded raw. The label BECOMES the recorded
    |             value (so `_group` hashes collapse naturally); the raw value
    |             is not retained. Config-file only — a map can't come from an
    |             env var. Empty by default: everything is captured raw.
    |
    | UPGRADING from a pre-`sensors` config: a published `filtering` block
    | (ignore_cache_events / ignore_cache_keys / ignore_queries /
    | ignore_query_patterns / ignore_job_names / ignore_outgoing_requests) is
    | still honoured — legacy keys win while present, so delete the block once
    | you have migrated. The old env vars keep working either way: the ignore
    | lists read the same names, and the enabled flags fall back to the old
    | inverted kill-switches.
    |
    | Head sampling (`sampling` above) is deliberately NOT per-sensor: it is
    | decided once per execution (agent protocol §3), so a trace is complete
    | or absent, never partial.
    |
    */

    'sensors' => [

        Sensors\RequestSensor::class => [
            'enabled' => env('DAYWATCH_REQUESTS_ENABLED', true),
        ],

        Sensors\QuerySensor::class => [
            'enabled' => env('DAYWATCH_QUERIES_ENABLED', ! env('DAYWATCH_IGNORE_QUERIES', false)),

            /*
             * Queries never recorded, matched against the raw SQL. The default
             * drops the framework's own internal tables — the database queue's
             * poll (`jobs`), the database cache store (`cache`, which also
             * covers `cache_locks`), the database session store (`sessions`)
             * and job batches (`batches`) — housekeeping noise every worker
             * emits and nothing an application can act on. Plain `*table*`
             * needles match any grammar quoting ("jobs" / `jobs` / [jobs]).
             * Set DAYWATCH_IGNORE_QUERY_PATTERNS to a comma-separated pattern
             * list to override, or to an empty string to record every query.
             * Beware a needle matches anywhere in the SQL: an application
             * table whose name merely contains one of these words is dropped
             * too — narrow the list if your schema collides.
             */
            'ignore' => env('DAYWATCH_IGNORE_QUERY_PATTERNS', '*jobs*,*cache*,*sessions*,*batches*'),
        ],

        Sensors\ExceptionSensor::class => [
            'enabled' => env('DAYWATCH_EXCEPTIONS_ENABLED', true),
        ],

        Sensors\CacheEventSensor::class => [
            'enabled' => env('DAYWATCH_CACHE_EVENTS_ENABLED', ! env('DAYWATCH_IGNORE_CACHE_EVENTS', false)),

            /*
             * Cache keys never recorded. The default drops Laravel's own
             * internal keys — `illuminate:queue:restart` is polled by every
             * queue worker on every loop, and nothing an application can act
             * on lives under that namespace. Set DAYWATCH_IGNORE_CACHE_KEYS
             * to a comma-separated pattern list to override, or to an empty
             * string to record every key.
             */
            'ignore' => env('DAYWATCH_IGNORE_CACHE_KEYS', '*illuminate:*'),

            /*
             * Cache-key grouping (Pulse-style): regex pattern ⇒ label, first
             * match wins. Hash- or id-suffixed keys that would each be their
             * own row on the dashboard collapse into one — e.g. with
             * `'#^sys_setting_.*$#' => 'sys_setting:*'` every
             * `sys_setting_5753f25f…` key records as `sys_setting:*`. The
             * label replaces the recorded key (the raw key is NOT retained),
             * replacements may use capture groups (`'user:$1:*'`), and a
             * pattern that fails to compile is skipped, never fatal. Empty by
             * default — every key is captured raw.
             */
            'groups' => [
                // '#^sys_setting_.*$#' => 'sys_setting:*',
                // '#^lookup_key_.*$#' => 'lookup_key:*',
                // '#:\d+#' => ':*',
            ],
        ],

        Sensors\LogSensor::class => [
            'enabled' => env('DAYWATCH_LOGS_ENABLED', true),

            // RESERVED — not yet read by the collector; setting it changes
            // nothing today. Listed so the option table stays in step.
            'level' => env('DAYWATCH_LOG_LEVEL', 'debug'),
        ],

        Sensors\MailSensor::class => [
            'enabled' => env('DAYWATCH_MAIL_ENABLED', true),
        ],

        Sensors\NotificationSensor::class => [
            'enabled' => env('DAYWATCH_NOTIFICATIONS_ENABLED', true),
        ],

        Sensors\OutgoingRequestSensor::class => [
            'enabled' => env('DAYWATCH_OUTGOING_REQUESTS_ENABLED', ! env('DAYWATCH_IGNORE_OUTGOING_REQUESTS', false)),
        ],

        Sensors\QueuedJobSensor::class => [
            'enabled' => env('DAYWATCH_QUEUED_JOBS_ENABLED', true),

            /*
             * Job names never recorded at dispatch, matched against the job's
             * display name / class. Empty by default; set
             * DAYWATCH_IGNORE_JOB_NAMES to a comma-separated pattern list,
             * e.g. `App\Jobs\Send*,*Horizon*`, to drop noisy jobs.
             */
            'ignore' => env('DAYWATCH_IGNORE_JOB_NAMES', ''),
        ],

        Sensors\JobAttemptSensor::class => [
            'enabled' => env('DAYWATCH_JOB_ATTEMPTS_ENABLED', true),

            // Same job-name ignore list as QueuedJobSensor, applied per
            // worker execution.
            'ignore' => env('DAYWATCH_IGNORE_JOB_NAMES', ''),
        ],

        Sensors\CommandSensor::class => [
            'enabled' => env('DAYWATCH_COMMANDS_ENABLED', true),
        ],

        Sensors\ScheduledTaskSensor::class => [
            'enabled' => env('DAYWATCH_SCHEDULED_TASKS_ENABLED', true),
        ],

    ],

    'redact_headers' => [
        // e.g. 'authorization', 'cookie', 'set-cookie', 'x-xsrf-token'
    ],

    'redact_payload_fields' => [
        // e.g. 'password', 'password_confirmation', 'token'
    ],

    /*
    |--------------------------------------------------------------------------
    | Local ingest (app → daemon, framed TCP — agent protocol §4)
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
