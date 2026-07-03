<?php

declare(strict_types=1);

namespace Daywatch\Agent\Console;

use Daywatch\Agent\Ingest\Client;
use Illuminate\Console\Command;
use Throwable;

/**
 * Daemon status probe (docs/agent-protocol.md §4–§5). Sends a `STATS` frame over
 * the local socket via the bound {@see Client} and renders the daemon's ingest
 * counters as a table (or raw JSON with --json); falls back to a `PING` for a
 * daemon that predates STATS. Never throws — an unreachable daemon is a clear
 * message and a non-zero exit, not an exception.
 */
class StatusCommand extends Command
{
    protected $signature = 'daywatch:status {--json : Output the daemon counters as JSON}';

    protected $description = 'Show whether the Daywatch agent daemon is running and its ingest counters.';

    public function handle(Client $client): int
    {
        $uri = (string) config('daywatch.ingest.uri', '127.0.0.1:2408');

        try {
            $stats = $client->stats();

            if ($stats !== null) {
                return $this->present($stats, $uri);
            }

            // A daemon that acks a PING but sends no STATS reply (older build,
            // or a token mismatch) is still up — report reachability only.
            if ($client->ping()) {
                if ($this->option('json')) {
                    $this->line('{}');
                } else {
                    $this->info('Daywatch agent is reachable at '.$uri.' (no counters reply — daemon predates STATS or token mismatch).');
                }

                return self::SUCCESS;
            }

            $this->error('Daywatch agent daemon unreachable at '.$uri.' — is `php artisan daywatch:agent` running?');

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('daywatch:status failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /** @param  array<string, mixed>  $stats */
    private function present(array $stats, string $uri): int
    {
        if ($this->option('json')) {
            $this->line((string) json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Daywatch agent is reachable at '.$uri.'.');
        $this->table(['Metric', 'Value'], $this->rows($stats));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $stats
     * @return list<array{string, string}>
     */
    private function rows(array $stats): array
    {
        $rows = [];

        foreach ($stats as $key => $value) {
            $rows[] = [(string) $key, $this->format((string) $key, $value)];
        }

        return $rows;
    }

    private function format(string $key, mixed $value): string
    {
        if ($key === 'last_flush_at') {
            if (! is_numeric($value)) {
                return 'never';
            }

            $ago = max(0, (int) round(microtime(true) - (float) $value));

            return gmdate('Y-m-d H:i:s', (int) $value).' UTC ('.$ago.'s ago)';
        }

        if ($value === null) {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return (string) json_encode($value);
    }
}
