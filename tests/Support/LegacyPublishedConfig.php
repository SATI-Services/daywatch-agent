<?php

declare(strict_types=1);

namespace Daywatch\Agent\Tests\Support;

/**
 * Simulates a host upgrading from 1.0.x with a PUBLISHED config from before
 * the `sensors` block: their config contributes the whole legacy `filtering`
 * array (evaluated env defaults) and no `sensors` key of their own — the
 * package's `sensors` block merges underneath (mergeConfigFrom is a shallow
 * top-level merge: package first, app second).
 *
 * A trait (not a TestCase subclass) because Pest binds the whole tests/
 * tree to the base TestCase recursively; traits compose without conflict,
 * and a trait method overrides the inherited defineEnvironment. The trait's
 * parent:: call resolves against the generated test class's parent — the
 * base TestCase — so the sqlite pin is kept.
 */
trait LegacyPublishedConfig
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Exactly what a 1.0.2-era published config/daywatch.php contributes.
        $app['config']->set('daywatch.filtering', [
            'ignore_queries' => false,
            'ignore_query_patterns' => '*jobs*,*cache*,*sessions*,*batches*',
            'ignore_cache_events' => false,
            'ignore_cache_keys' => '*illuminate:*',
            'ignore_job_names' => '',
            'ignore_outgoing_requests' => false,
            'log_level' => 'debug',
        ]);
    }
}
