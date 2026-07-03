<?php

declare(strict_types=1);

namespace Daywatch\Agent\Console;

use Daywatch\Agent\Daemon\BatchBuffer;
use Daywatch\Agent\Daemon\ConnectionHandler;
use Daywatch\Agent\Daemon\DaemonStats;
use Daywatch\Agent\Daemon\IngestDispatcher;
use Daywatch\Agent\Daemon\IngestServer;
use Daywatch\Agent\Daemon\LoopScheduler;
use Daywatch\Agent\Daemon\SocketHttpSender;
use Daywatch\Agent\Daemon\StatsReporter;
use Daywatch\Agent\Ingest\Payload;
use Daywatch\Agent\Support\SystemClock;
use Illuminate\Console\Command;
use React\EventLoop\Loop;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\TcpServer;
use Throwable;

/**
 * The local telemetry daemon (docs/agent-protocol.md §4–§6). Boots a ReactPHP
 * StreamSelectLoop + TcpServer that accepts framed digests, string-level batches
 * them ({@see BatchBuffer}), gzips, and POSTs to {base_url}/api/ingest — the POST
 * transport is raw HTTP/1.1 over react/socket ({@see SocketHttpSender}), not
 * react/http (which would pin psr/http-message < 2.0 and break Laravel 13 hosts).
 */
class AgentCommand extends Command
{
    protected $signature = 'daywatch:agent {--listen= : Override the listen address (host:port)}';

    protected $description = 'Run the Daywatch local TCP ingest daemon (ReactPHP).';

    public function handle(): int
    {
        try {
            $baseUrl = (string) (config('daywatch.base_url') ?? '');

            if ($baseUrl === '') {
                $this->error('DAYWATCH_BASE_URL is not set — nothing to forward telemetry to.');

                return self::FAILURE;
            }

            $listen = (string) ($this->option('listen') ?: config('daywatch.ingest.uri', '127.0.0.1:2408'));
            $token = config('daywatch.token');

            $loop = Loop::get();
            $logger = fn (string $message) => $this->line($message);
            $scheduler = new LoopScheduler($loop);
            $clock = new SystemClock;

            // Operator-facing ingest counters (docs/agent-protocol.md §4 STATS, §5 stats log).
            $stats = new DaemonStats(rtrim($baseUrl, '/'), $clock);

            $sender = new SocketHttpSender(
                new Connector(['timeout' => (float) config('daywatch.agent.connect_timeout', 5)], $loop),
                $loop,
                (float) config('daywatch.agent.request_timeout', 10),
            );

            $dispatcher = new IngestDispatcher(
                sender: $sender,
                scheduler: $scheduler,
                url: rtrim($baseUrl, '/').'/api/ingest',
                token: (string) $token,
                server: (string) (config('daywatch.server') ?? ''),
                userAgent: 'DaywatchAgent/'.$this->packageVersion(),
                maxConcurrent: (int) config('daywatch.agent.max_concurrent_requests', 5),
                logger: $logger,
                stats: $stats,
            );

            $server = new IngestServer(
                new BatchBuffer(
                    $clock,
                    (int) config('daywatch.agent.flush_bytes', 6_000_000),
                    (int) config('daywatch.agent.flush_interval', 10),
                ),
                $dispatcher,
                $stats,
            );

            $tokenHash = Payload::tokenHash($token);

            $tcp = new TcpServer($listen, $loop);

            $tcp->on('connection', function (ConnectionInterface $conn) use ($server, $tokenHash, $logger, $loop, $stats): void {
                $handler = new ConnectionHandler(
                    expectedTokenHash: $tokenHash,
                    server: $server,
                    ackWriter: static fn (string $ack) => $conn->write($ack),
                    onUnknownVersion: static function () use ($server, $loop): void {
                        $server->finalDigest();
                        $loop->stop();
                    },
                    onError: static fn (string $message) => $conn->close(),
                    logger: $logger,
                    statsResponder: static fn (): string => $stats->toJson(),
                );

                $conn->on('data', static fn (string $data) => $handler->feed($data));
                $conn->on('error', static fn () => $conn->close());
            });

            $tcp->on('error', static fn (Throwable $e) => $logger('[daywatch:agent] server error: '.$e->getMessage()));

            // Age-based (10 s) flush drain.
            $loop->addPeriodicTimer(1.0, static fn () => $server->tick());

            // Periodic operator stats line on stdout (0 disables). Fully guarded —
            // a logging failure can never crash the loop.
            (new StatsReporter(
                $stats,
                $scheduler,
                $logger,
                (int) config('daywatch.daemon.stats_interval', 60),
            ))->start();

            $this->info('Daywatch agent listening on '.$listen.' → '.rtrim($baseUrl, '/').'/api/ingest');

            $loop->run();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('daywatch:agent failed to start: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function packageVersion(): string
    {
        return '1.x';
    }
}
