<?php

declare(strict_types=1);

namespace Daywatch\Agent\Tests\Support;

use RuntimeException;

/**
 * Launches {@see tcp_server.php} in a child process and exposes its ephemeral port.
 * Readiness and completion are both signalled through the child's stdout (blocking
 * reads), so there are NO sleeps and no bind/timing races.
 */
final class StubTcpServer
{
    /** @var resource */
    private $proc;

    /** @var array<int, resource> */
    private array $pipes;

    private readonly int $port;

    private readonly string $capturePath;

    private ?string $trailingStdout = null;

    public function __construct(string $ack = '2:OK', string $reply = '')
    {
        $this->capturePath = (string) tempnam(sys_get_temp_dir(), 'dw-stub-');

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $command = [PHP_BINARY, __DIR__.'/tcp_server.php', '--ack='.$ack, '--capture='.$this->capturePath];

        if ($reply !== '') {
            $command[] = '--reply='.$reply;
        }

        $proc = proc_open(
            $command,
            $descriptors,
            $pipes,
        );

        if (! is_resource($proc)) {
            throw new RuntimeException('failed to launch stub TCP server');
        }

        $this->proc = $proc;
        $this->pipes = $pipes;

        $line = fgets($this->pipes[1]);

        if ($line === false || ! str_starts_with($line, 'PORT=')) {
            $this->stop();
            throw new RuntimeException('stub TCP server did not report a port');
        }

        $this->port = (int) trim(substr($line, 5));
    }

    public function uri(): string
    {
        return '127.0.0.1:'.$this->port;
    }

    /** Block (sleep-free) until the child has finished, then read the captured frame. */
    public function capturedFrame(): string
    {
        $this->drain();

        return (string) @file_get_contents($this->capturePath);
    }

    public function stop(): void
    {
        $this->drain();

        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                @fclose($pipe);
            }
        }

        if (is_resource($this->proc)) {
            @proc_close($this->proc);
        }

        @unlink($this->capturePath);
    }

    /** Reading stdout to EOF returns only when the child closes it — i.e. exits. */
    private function drain(): void
    {
        if ($this->trailingStdout === null && isset($this->pipes[1]) && is_resource($this->pipes[1])) {
            $this->trailingStdout = (string) stream_get_contents($this->pipes[1]);
        }
    }
}
