<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon\Contracts;

use Daywatch\Agent\Daemon\ConnectionHandler;
use Daywatch\Agent\Daemon\IngestServer;

/**
 * The sink a {@see ConnectionHandler} hands validated record
 * payloads to. Implemented by {@see IngestServer}; stubbed
 * in tests to assert exactly what a connection forwards.
 */
interface RecordSink
{
    /** Accept one digest payload (a `[...]` JSON array string). */
    public function ingest(string $payload): void;
}
