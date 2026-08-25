<?php

declare(strict_types=1);

use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\ExceptionRecord;
use Daywatch\Agent\Support\Group;

it('serializes the exact exception wire payload', function () {
    $record = new ExceptionRecord(
        envelope: new Envelope(
            timestamp: 1751446800.092850,
            deploy: 'abc1234',
            server: 'demo-01',
            traceId: '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
            user: '42',
            executionId: '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
            executionSource: 'request',
            executionPreview: 'GET /orders/{order}',
            executionStage: 'action',
        ),
        class: 'App\\Exceptions\\PaymentRetried',
        file: 'app/Services/PaymentService.php',
        line: 87,
        message: 'Retrying charge after gateway timeout',
        code: '0',
        trace: '[{"file":"app/Services/PaymentService.php:87","source":"PaymentService->charge(Order)","code":null}]',
        handled: true,
        phpVersion: '8.4.8',
        laravelVersion: '13.18.0',
    );

    expect($record->toArray())->toBe([
        'v' => 1,
        't' => 'exception',
        'timestamp' => 1751446800.092850,
        'deploy' => 'abc1234',
        'server' => 'demo-01',
        '_group' => Group::exception('App\\Exceptions\\PaymentRetried', '0', 'app/Services/PaymentService.php', 87),
        'trace_id' => '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
        'execution_id' => '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
        'execution_source' => 'request',
        'execution_preview' => 'GET /orders/{order}',
        'execution_stage' => 'action',
        'user' => '42',
        'class' => 'App\\Exceptions\\PaymentRetried',
        'file' => 'app/Services/PaymentService.php',
        'line' => 87,
        'message' => 'Retrying charge after gateway timeout',
        'code' => '0',
        'trace' => '[{"file":"app/Services/PaymentService.php:87","source":"PaymentService->charge(Order)","code":null}]',
        'handled' => true,
        'php_version' => '8.4.8',
        'laravel_version' => '13.18.0',
    ]);
});

it('keeps the code as a string (per the wire contract)', function () {
    $record = new ExceptionRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        class: 'X', file: 'f', line: 1, message: 'm', code: '42', trace: '[]',
        handled: false, phpVersion: '8.2', laravelVersion: '11',
    );

    expect($record->toArray()['code'])->toBe('42')->toBeString()
        ->and($record->toArray()['handled'])->toBeFalse();
});
