<?php

declare(strict_types=1);

namespace Daywatch\Agent\Facades;

use Daywatch\Agent\Daywatch as DaywatchManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static DaywatchManager user(string|int|callable|null $id)
 * @method static DaywatchManager sample()
 * @method static DaywatchManager dontSample()
 * @method static DaywatchManager report(\Throwable $e)
 * @method static DaywatchManager ignore(\Throwable $e)
 * @method static DaywatchManager pause()
 * @method static DaywatchManager resume()
 * @method static DaywatchManager digest()
 *
 * @see DaywatchManager
 */
class Daywatch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DaywatchManager::class;
    }
}
