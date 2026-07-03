<?php

declare(strict_types=1);

namespace Daywatch\Agent\Tests\Support;

use Daywatch\Agent\Daemon\Contracts\RecordSink;

/** Records the raw payloads a ConnectionHandler forwards. */
final class SpyRecordSink implements RecordSink
{
    /** @var list<string> */
    public array $ingested = [];

    public function ingest(string $payload): void
    {
        $this->ingested[] = $payload;
    }
}
