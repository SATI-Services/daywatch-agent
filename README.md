# Daywatch Agent

<p>
    <a href="https://github.com/daywatch/agent/actions"><img src="https://github.com/daywatch/agent/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
    <a href="https://packagist.org/packages/daywatch/agent"><img src="https://img.shields.io/packagist/v/daywatch/agent" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/daywatch/agent"><img src="https://img.shields.io/packagist/l/daywatch/agent" alt="License"></a>
</p>

Daywatch is a telemetry collector for Laravel applications. Sensors observe
requests, database queries, exceptions, jobs, commands, and scheduled tasks into
a bounded per-execution buffer, digest them over a local framed-TCP daemon
(built on [ReactPHP](https://reactphp.org/)), which batches, gzips, and POSTs to
your central Daywatch ingest.

The package is designed to be **un-crashable**: every event hook, socket write,
and encode is wrapped and failures are swallowed. Worst case with a dead daemon
is a sub-second digest timeout — never a user-visible exception. Telemetry loss
is acceptable; host-application impact never is.

## Requirements

- PHP `^8.2`
- Laravel `11.x`, `12.x`, or `13.x`
- `ext-zlib`

## Installation

Install via Composer:

```bash
composer require daywatch/agent
```

The service provider and `Daywatch` facade are registered automatically via
[package discovery](https://laravel.com/docs/13.x/packages#package-discovery) —
no manual wiring required.

Optionally publish the configuration file:

```bash
php artisan vendor:publish --tag=daywatch-config
```

## Configuration

`DAYWATCH_TOKEN` and `DAYWATCH_BASE_URL` are required to transmit telemetry.
Without them the application runs normally and simply collects nothing.

```dotenv
DAYWATCH_ENABLED=true
DAYWATCH_TOKEN=dw_your_environment_token
DAYWATCH_BASE_URL=https://daywatch.example.com

# Local daemon address (app → daemon). Use 0.0.0.0:2408 in Docker.
DAYWATCH_INGEST_URI=127.0.0.1:2408

# Release marker attached to every record.
DAYWATCH_DEPLOY=

# Head-sampling rates (1.0 = keep everything). Errors escape sampled-out traces.
DAYWATCH_REQUEST_SAMPLE_RATE=1.0
DAYWATCH_EXCEPTION_SAMPLE_RATE=1.0
```

The full option table lives in the published `config/daywatch.php`.

## Running the daemon

Run one long-running daemon per application, supervised by systemd, Supervisor,
or Docker. It accepts digests over TCP, batches and gzips them, and POSTs to the
ingest:

```bash
php artisan daywatch:agent
```

Check that the daemon is alive and moving records:

```bash
php artisan daywatch:status          # human-readable table
php artisan daywatch:status --json   # machine-readable; exit 1 when down
```

## Runtime API

Use the `Daywatch` facade inside a request, job, or command to steer collection.
Every call is safe and never throws:

```php
use Daywatch\Agent\Facades\Daywatch;

Daywatch::user($request->user()?->id); // attribute this execution to a user
Daywatch::sample();                    // force-keep this execution
Daywatch::dontSample();                // force-drop this execution
Daywatch::report($e);                  // record an exception (escapes sampling)
Daywatch::ignore($e);                  // suppress an exception
Daywatch::pause();                     // stop collecting for the rest of this execution
Daywatch::resume();                    // resume after a pause()
Daywatch::digest();                    // flush the buffer to the daemon now
```

## Inspecting the install

Daywatch contributes a section to Laravel's `about` command:

```bash
php artisan about
```

## Testing

```bash
composer test
```

The suite runs against the Laravel 11, 12, and 13 lines in CI. A "hostile host"
suite proves nothing throws when the daemon is down, the token is wrong, or
payloads are oversized.

## License

Daywatch Agent is open-sourced software licensed under the [MIT license](LICENSE).
