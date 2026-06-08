<?php

use Pinoox\Portal\Database\DB;

it('allows generic CLI boot with an invalid DB_CONNECTION until DB is used', function () {
    $root = testProjectRoot();
    $env = array_merge($_ENV, [
        'DB_CONNECTION' => 'mysqltest',
        'APP_ENV' => 'development',
    ]);

    $process = proc_open(
        [PHP_BINARY, $root . DIRECTORY_SEPARATOR . 'pinoox', 'mode:show'],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $root,
        $env,
    );

    expect($process)->not->toBeFalse();

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('mysqltest')
        ->and($stderr)->not->toContain('is not defined');
});

it('fails DB config resolution when DB_CONNECTION is invalid', function () {
    putenv('DB_CONNECTION=mysqltest');
    $_ENV['DB_CONNECTION'] = 'mysqltest';
    $_SERVER['DB_CONNECTION'] = 'mysqltest';

    expect(fn () => DB::getConfig())
        ->toThrow(\InvalidArgumentException::class, 'mysqltest');

    putenv('DB_CONNECTION');
    unset($_ENV['DB_CONNECTION'], $_SERVER['DB_CONNECTION']);
});
