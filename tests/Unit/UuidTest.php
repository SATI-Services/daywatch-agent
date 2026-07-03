<?php

declare(strict_types=1);

use Daywatch\Agent\Support\Uuid;

it('generates an RFC-4122 v4 UUID', function () {
    $uuid = Uuid::v4();

    expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

it('generates unique ids', function () {
    $ids = array_map(fn () => Uuid::v4(), range(1, 200));

    expect(array_unique($ids))->toHaveCount(200);
});
