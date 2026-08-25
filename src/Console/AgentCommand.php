<?php

declare(strict_types=1);

namespace Daywatch\Agent\Console;

use Daywatch\Agent\Daemon\AuthProbe;
use Daywatch\Agent\Daemon\BatchBuffer;
use Daywatch\Agent\Daemon\ConnectionHandler;
use Daywatch\Agent\Daemon\ConsoleDashboard;
use Daywatch\Agent\Daemon\DaemonStats;
use Daywatch\Agent\Daemon\IngestDispatcher;
use Daywatch\Agent\Daemon\IngestServer;
use Daywatch\Agent\Daemon\LoopScheduler;
use Daywatch\Agent\Daemon\RecentLog;
use Daywatch\Agent\Daemon\SocketHttpSender;
use Daywatch\Agent\Daemon\StatsReporter;
use Daywatch\Agent\Ingest\Payload;
use Daywatch\Agent\Support\Clock;
use Daywatch\Agent\Support\SystemClock;
use Illuminate\Console\Command;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\TcpServer;
use Throwable;

/**
 * The local telemetry daemon (agent protocol §4–§6). Boots a ReactPHP
 * StreamSelectLoop + TcpServer that accepts framed digests, string-level batches
 * them ({@see BatchBuffer}), gzips, and POSTs to {base_url}/api/ingest — the POST
 * transport is raw HTTP/1.1 over react/socket ({@see SocketHttpSender}), not
 * react/http (which would pin psr/http-message < 2.0 and break Laravel 13 hosts).
 */
class AgentCommand extends Command
{
    protected $signature = 'daywatch:agent
        {--listen= : Override the listen address (host:port)}
        {--plain : Disable the live dashboard — scroll plain log lines (for supervisors/log files)}
        {--no-auth-check : Skip the startup authentication check against the ingest}';

    protected $description = 'Run the Daywatch local TCP ingest daemon (ReactPHP).';

    public function handle(): int
    {
        $listen = '';

        try {
            $baseUrl = (string) (config('daywatch.base_url') ?? '');

            if ($baseUrl === '') {
                $this->error('DAYWATCH_BASE_URL is not set — nothing to forward telemetry to.');

                return self::FAILURE;
            }

            $listen = (string) ($this->option('listen') ?: config('daywatch.ingest.uri', '127.0.0.1:2408'));
            $token = config('daywatch.token');

            $loop = Loop::get();
            $scheduler = new LoopScheduler($loop);
            $clock = new SystemClock;

            // Live dashboard when attached to a TTY (unless --plain / refresh 0):
            // the daemon's logger feeds a bounded ring buffer that the dashboard
            // shows as the last N lines, instead of scrolling stdout forever.
            $refresh = (int) config('daywatch.daemon.console_refresh', 3);
            $dashboardMode = ! $this->option('plain') && $refresh > 0 && $this->attachedToTty();
            $recent = new RecentLog((int) config('daywatch.daemon.console_lines', 10));

            $logger = $dashboardMode
                ? fn (string $message) => $recent->push($message)
                : fn (string $message) => $this->line($message);

            // Operator-facing ingest counters (agent protocol §4 STATS, §5 stats log).
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

            $shuttingDown = false;

            $tcp->on('connection', function (ConnectionInterface $conn) use ($server, $tokenHash, $logger, $loop, $stats, &$shuttingDown): void {
                $handler = new ConnectionHandler(
                    expectedTokenHash: $tokenHash,
                    server: $server,
                    ackWriter: static fn (string $ack) => $conn->write($ack),
                    onUnknownVersion: static function () use ($server, $loop, &$shuttingDown): void {
                        $shuttingDown = true;
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

            if ($dashboardMode) {
                // The dashboard owns the screen: it already renders the ingest
                // counters, so the scrolling stats line would corrupt the redraw.
                (new ConsoleDashboard(
                    $stats,
                    $recent,
                    $clock,
                    $scheduler,
                    fn (string $s) => $this->output->write($s, false),
                    $listen,
                    $refresh,
                ))->start();
            } else {
                // Periodic operator stats line on stdout (0 disables). Fully guarded —
                // a logging failure can never crash the loop.
                (new StatsReporter(
                    $stats,
                    $scheduler,
                    $logger,
                    (int) config('daywatch.daemon.stats_interval', 60),
                ))->start();

                $this->info('Daywatch agent listening on '.$listen.' → '.rtrim($baseUrl, '/').'/api/ingest');
            }

            // Startup authentication check (daemon.auth_check / --no-auth-check):
            // POST an empty batch NOW, so a wrong DAYWATCH_TOKEN / DAYWATCH_BASE_URL or
            // a down ingest is reported at boot instead of surfacing later as silently
            // dropped telemetry. Diagnostic only — it settles inside the loop and
            // never blocks or stops the daemon.
            if ($this->shouldCheckAuth()) {
                (new AuthProbe(
                    sender: $sender,
                    url: rtrim($baseUrl, '/').'/api/ingest',
                    token: (string) $token,
                    server: (string) (config('daywatch.server') ?? ''),
                    userAgent: 'DaywatchAgent/'.$this->packageVersion(),
                    stats: $stats,
                    logger: $logger,
                ))->run();
            }

            // CARDINAL RULE: the daemon must never die on an escaped error. If an
            // exception ever propagates out of a ReactPHP callback, log it and
            // re-enter the loop (listeners/timers stay armed) rather than exit.
            $this->runResiliently($loop, $clock, $recent, $shuttingDown);

            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($this->addressInUse($e)) {
                return $this->reportAddressInUse($listen);
            }

            $this->error('daywatch:agent failed to start: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /** The startup auth check is on unless disabled by flag or config. */
    private function shouldCheckAuth(): bool
    {
        return ! $this->option('no-auth-check')
            && (bool) config('daywatch.daemon.auth_check', true);
    }

    /**
     * The single most common startup failure: something already holds the listen
     * port — almost always another `daywatch:agent`. Say so, and say what to do,
     * instead of leaking a raw ReactPHP socket message.
     */
    private function addressInUse(Throwable $e): bool
    {
        $message = $e->getMessage();

        while (true) {
            if (stripos($message, 'EADDRINUSE') !== false || stripos($message, 'Address in use') !== false
                || stripos($message, 'Address already in use') !== false) {
                return true;
            }

            $e = $e->getPrevious();

            if ($e === null) {
                return false;
            }

            $message = $e->getMessage();
        }
    }

    private function reportAddressInUse(string $listen): int
    {
        $listen = $listen === '' ? (string) config('daywatch.ingest.uri', '127.0.0.1:2408') : $listen;
        $port = str_contains($listen, ':') ? substr((string) strrchr($listen, ':'), 1) : $listen;

        $this->error('daywatch:agent cannot listen on '.$listen.' — that address is already in use.');
        $this->line('Another daywatch:agent daemon is most likely already running.');
        $this->line('  • confirm it:        php artisan daywatch:status');
        $this->line('  • find the process:  lsof -nP -iTCP:'.$port.' -sTCP:LISTEN');
        $this->line('  • or listen elsewhere: php artisan daywatch:agent --listen=127.0.0.1:'.((int) $port + 1));

        return self::FAILURE;
    }

    /**
     * Run the event loop and keep it alive across escaped exceptions. A clean
     * return means a deliberate shutdown (final digest already flushed); a throw
     * means a callback leaked — we record it and re-enter the loop. Rapid repeat
     * failures are bounded so a pathological callback can't pin the CPU.
     */
    private function runResiliently(LoopInterface $loop, Clock $clock, RecentLog $recent, bool &$shuttingDown): void
    {
        $consecutive = 0;
        $lastErrorAt = 0.0;

        while (true) {
            try {
                $loop->run();

                return; // loop stopped: deliberate shutdown or nothing left to serve
            } catch (Throwable $e) {
                if ($shuttingDown) {
                    return;
                }

                $now = $clock->microtime();

                if ($now - $lastErrorAt > 5.0) {
                    $consecutive = 0; // recovered and ran healthily for a while — reset
                }

                $lastErrorAt = $now;
                $consecutive++;

                try {
                    $recent->push('[daywatch:agent] recovered from loop error: '.$e->getMessage());
                } catch (Throwable) {
                }

                if ($consecutive > 50) {
                    // Immediate re-throws with no progress: bail rather than hot-loop.
                    return;
                }
            }
        }
    }

    /** True only when stdout is an interactive terminal (never in tests/pipes). */
    private function attachedToTty(): bool
    {
        return defined('STDOUT') && function_exists('stream_isatty') && @stream_isatty(STDOUT);
    }

    private function packageVersion(): string
    {
        return '1.x';
    }
}
