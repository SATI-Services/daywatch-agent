<?php

declare(strict_types=1);

namespace Daywatch\Agent\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Record a deploy marker (services/daywatch-mcp/docs/agent-protocol.md §6, "Deploy markers"). POSTs to
 * {base_url}/api/deploys via the Laravel HTTP client with a Bearer token. Runs on
 * release so dashboards can annotate metrics and resolve-on-deploy issues fire.
 */
class DeployCommand extends Command
{
    protected $signature = 'daywatch:deploy {deploy? : The deploy identifier}
                            {--ref= : VCS ref (commit sha / tag)}
                            {--name= : Human-readable release name}
                            {--url= : Link to the release / changelog}';

    protected $description = 'Send a deploy marker to the Daywatch ingest.';

    public function handle(): int
    {
        $baseUrl = (string) (config('daywatch.base_url') ?? '');
        $token = (string) (config('daywatch.token') ?? '');

        if ($baseUrl === '') {
            $this->error('DAYWATCH_BASE_URL is not set.');

            return self::FAILURE;
        }

        $deploy = (string) ($this->argument('deploy') ?? config('daywatch.deployment') ?? '');

        if ($deploy === '') {
            $this->error('No deploy identifier given (argument or DAYWATCH_DEPLOY).');

            return self::FAILURE;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($baseUrl, '/').'/api/deploys', [
                    'timestamp' => Carbon::now('UTC')->format('Y-m-d H:i:s.u'),
                    'deploy' => $deploy,
                    'ref' => (string) ($this->option('ref') ?? ''),
                    'name' => (string) ($this->option('name') ?? ''),
                    'url' => (string) ($this->option('url') ?? ''),
                ]);

            if ($response->successful()) {
                $this->info('Deploy marker "'.$deploy.'" recorded.');

                return self::SUCCESS;
            }

            $this->error('Deploy marker failed: HTTP '.$response->status().'.');

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('daywatch:deploy failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
