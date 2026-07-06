<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\UserRecord;
use Throwable;

/**
 * UserSensor — emits one `user` record per execution when an authenticated user
 * is present (daywatch-mcp/docs/agent-protocol.md §2). The user id also travels inline on every
 * other record's `user` field; this record additionally carries name/username for
 * the dashboard's user directory. `Daywatch::user(fn ($user) => [...])` customises
 * the resolved `{id, name, username}`.
 *
 * CARDINAL RULE: resolution is fully guarded — a broken auth guard or user model
 * can never disturb the host execution.
 */
final class UserSensor
{
    /** @var (callable(mixed): array<string, mixed>)|null */
    private $customizer = null;

    public function __construct(private Core $core) {}

    /** Register a customiser mapping the auth user to `['id'=>, 'name'=>, 'username'=>]`. */
    public function using(callable $customizer): void
    {
        $this->customizer = $customizer;
    }

    /** Capture the current authenticated user into a `user` record, if any. */
    public function capture(): void
    {
        try {
            $user = $this->authUser();

            if ($user === null) {
                return;
            }

            [$id, $name, $username] = $this->resolve($user);

            if ($id === '') {
                return;
            }

            $record = new UserRecord(
                timestamp: $this->core->clock()->microtime(),
                deploy: $this->core->deploy(),
                server: $this->core->server(),
                id: $id,
                name: $name,
                username: $username,
            );

            $this->core->record($record->toArray());
        } catch (Throwable) {
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string} [id, name, username]
     */
    private function resolve(mixed $user): array
    {
        if ($this->customizer !== null) {
            try {
                $custom = ($this->customizer)($user);

                return [
                    (string) ($custom['id'] ?? ''),
                    (string) ($custom['name'] ?? ''),
                    (string) ($custom['username'] ?? ''),
                ];
            } catch (Throwable) {
            }
        }

        $id = '';
        if (is_object($user) && method_exists($user, 'getAuthIdentifier')) {
            $id = (string) ($user->getAuthIdentifier() ?? '');
        }

        return [$id, $this->attr($user, 'name'), $this->attr($user, 'username')];
    }

    private function attr(mixed $user, string $key): string
    {
        try {
            if (is_object($user) && isset($user->{$key})) {
                return (string) $user->{$key};
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function authUser(): mixed
    {
        try {
            if (function_exists('auth')) {
                return auth()->user();
            }
        } catch (Throwable) {
        }

        return null;
    }
}
