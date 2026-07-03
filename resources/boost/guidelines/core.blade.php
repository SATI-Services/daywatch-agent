# Daywatch (daywatch/agent)

Telemetry collector installed in this app. Sensors observe requests, queries,
exceptions, jobs, commands & scheduled tasks, buffer them per execution, and
digest over a local TCP daemon that batches and ships to your Daywatch instance.
The package is designed to be un-crashable: telemetry loss is fine, host-app
impact never is.

## Configure

Publish the config (optional — sane defaults ship):

```bash
php artisan vendor:publish --tag=daywatch-config
```

Set the environment. `DAYWATCH_TOKEN` and `DAYWATCH_BASE_URL` are required to
transmit; without them the app runs normally and collects nothing.

```dotenv
DAYWATCH_ENABLED=true
DAYWATCH_TOKEN=dw_your_environment_token
DAYWATCH_BASE_URL=https://daywatch.example.com

# Local daemon address (app → daemon). Use 0.0.0.0:2408 in Docker.
DAYWATCH_INGEST_URI=127.0.0.1:2408

# Release marker shown against every record.
DAYWATCH_DEPLOY=

# Head-sampling rates (1.0 = keep everything). Errors escape sampled-out traces.
DAYWATCH_REQUEST_SAMPLE_RATE=1.0
DAYWATCH_EXCEPTION_SAMPLE_RATE=1.0
```

## Run the daemon

One long-running daemon per app, supervised (systemd / Supervisor / Docker).
It accepts digests over TCP, batches + gzips them, and POSTs to the ingest.

```bash
php artisan daywatch:agent
```

Check reachability:

```bash
php artisan daywatch:status
```

## Mark deploys

Send a deploy marker on each release so charts get annotated:

```bash
php artisan daywatch:deploy "$(git rev-parse --short HEAD)" \
    --ref="$(git rev-parse HEAD)" --name="v1.4.0"
```

## Runtime API (`Daywatch` facade)

Use inside a request / job / command to steer collection. All calls are safe —
they never throw:

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
