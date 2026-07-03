<?php

declare(strict_types=1);

namespace Daywatch\Agent;

use Daywatch\Agent\Buffer\RecordsBuffer;
use Daywatch\Agent\Console\AgentCommand;
use Daywatch\Agent\Console\DeployCommand;
use Daywatch\Agent\Console\StatusCommand;
use Daywatch\Agent\Ingest\Client;
use Daywatch\Agent\Ingest\Payload;
use Daywatch\Agent\Ingest\SocketClient;
use Daywatch\Agent\Sensors\ExceptionSensor;
use Daywatch\Agent\Sensors\QuerySensor;
use Daywatch\Agent\Sensors\RequestSensor;
use Daywatch\Agent\Support\Clock;
use Daywatch\Agent\Support\SystemClock;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Register-only wiring for the Daywatch collector.
 *
 * CARDINAL RULE: this package must be un-crashable. Nothing here may throw into
 * the host application. register() stashes any boot exception and boot() reports
 * it (log-only) instead of propagating it — see packages/daywatch-agent/CLAUDE.md.
 */
class AgentServiceProvider extends ServiceProvider
{
    /** Exception stashed during register() so a boot failure can never crash the host. */
    protected ?Throwable $bootException = null;

    public function register(): void
    {
        try {
            $this->mergeConfigFrom(__DIR__.'/../config/daywatch.php', 'daywatch');

            $this->app->singleton(Clock::class, SystemClock::class);

            $this->app->singleton(RecordsBuffer::class, fn (): RecordsBuffer => new RecordsBuffer(
                (int) config('daywatch.ingest.event_buffer', 500),
            ));

            $this->app->singleton(Client::class, fn (): Client => new SocketClient(
                (string) config('daywatch.ingest.uri', '127.0.0.1:2408'),
                Payload::tokenHash(config('daywatch.token')),
                (float) config('daywatch.ingest.connection_timeout', 0.5),
                (float) config('daywatch.ingest.timeout', 0.5),
            ));

            $this->app->singleton(Core::class, fn ($app): Core => new Core(
                $app->make(RecordsBuffer::class),
                $app->make(Client::class),
                $app->make(Clock::class),
                (bool) config('daywatch.enabled', true),
                (string) (config('daywatch.deployment') ?? ''),
                (string) (config('daywatch.server') ?? ''),
                (float) config('daywatch.sampling.requests', 1.0),
                (float) config('daywatch.sampling.commands', 1.0),
                (float) config('daywatch.sampling.exceptions', 1.0),
                (bool) config('daywatch.capture_exception_source_code', true),
            ));

            $this->app->singleton(RequestSensor::class, fn ($app): RequestSensor => new RequestSensor($app->make(Core::class)));
            $this->app->singleton(QuerySensor::class, fn ($app): QuerySensor => new QuerySensor($app->make(Core::class)));
            $this->app->singleton(ExceptionSensor::class, fn ($app): ExceptionSensor => new ExceptionSensor($app->make(Core::class)));
            $this->app->singleton(SensorManager::class, fn ($app): SensorManager => new SensorManager($app));

            $this->app->singleton(Daywatch::class, fn ($app): Daywatch => new Daywatch(
                $app->make(Core::class),
                $app->make(ExceptionSensor::class),
            ));
        } catch (Throwable $e) {
            $this->bootException = $e;
        }
    }

    public function boot(): void
    {
        try {
            if ($this->bootException !== null) {
                $this->reportBootException($this->bootException);
                $this->bootException = null;

                return;
            }

            if ($this->app->runningInConsole()) {
                $this->publishes([
                    __DIR__.'/../config/daywatch.php' => $this->app->configPath('daywatch.php'),
                ], 'daywatch-config');

                $this->commands([
                    AgentCommand::class,
                    StatusCommand::class,
                    DeployCommand::class,
                ]);
            }

            if ((bool) config('daywatch.enabled', true)) {
                $this->app->make(SensorManager::class)->register();
            }
        } catch (Throwable $e) {
            $this->reportBootException($e);
        }
    }

    /** Report a boot failure without ever throwing — even the logger call is guarded. */
    protected function reportBootException(Throwable $e): void
    {
        try {
            if ($this->app->bound('log')) {
                $this->app->make('log')->error('[daywatch] boot failed: '.$e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        } catch (Throwable) {
            // Swallow — telemetry loss is acceptable, host-app impact is not.
        }
    }
}
