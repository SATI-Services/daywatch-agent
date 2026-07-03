<?php

declare(strict_types=1);

it('fails fast (without booting the loop) when base_url is not configured', function () {
    config(['daywatch.base_url' => null]);

    $this->artisan('daywatch:agent')
        ->expectsOutputToContain('DAYWATCH_BASE_URL is not set')
        ->assertExitCode(1);
});
