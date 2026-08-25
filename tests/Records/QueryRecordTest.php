<?php

declare(strict_types=1);

use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\QueryRecord;
use Daywatch\Agent\Support\Group;

it('serializes the exact query wire payload', function () {
    $record = new QueryRecord(
        envelope: new Envelope(
            timestamp: 1751446800.031400,
            deploy: 'abc1234',
            server: 'demo-01',
            traceId: '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
            user: '42',
            executionId: '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
            executionSource: 'request',
            executionPreview: 'GET /orders/{order}',
            executionStage: 'action',
        ),
        sql: 'select * from `orders` where `id` = ? limit 1',
        file: 'app/Http/Controllers/OrderController.php',
        line: 38,
        duration: 8400,
        connection: 'mysql',
        connectionType: 'read',
    );

    expect($record->toArray())->toBe([
        'v' => 1,
        't' => 'query',
        'timestamp' => 1751446800.031400,
        'deploy' => 'abc1234',
        'server' => 'demo-01',
        '_group' => Group::query('mysql', 'select * from `orders` where `id` = ? limit 1'),
        'trace_id' => '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
        'execution_id' => '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
        'execution_source' => 'request',
        'execution_preview' => 'GET /orders/{order}',
        'execution_stage' => 'action',
        'user' => '42',
        'sql' => 'select * from `orders` where `id` = ? limit 1',
        'file' => 'app/Http/Controllers/OrderController.php',
        'line' => 38,
        'duration' => 8400,
        'connection' => 'mysql',
        'connection_type' => 'read',
    ]);
});

it('transmits SQL raw — bindings are never substituted', function () {
    $record = new QueryRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        sql: 'select * from users where email = ? and status = ?',
        file: '', line: 0, duration: 0, connection: 'mysql', connectionType: 'read',
    );

    expect($record->toArray()['sql'])->toBe('select * from users where email = ? and status = ?');
});
