<?php

use Pinoox\Component\Server\DevelopmentServer;
use Pinoox\Terminal\Serve\ServeCommand;

it('registers the serve command', function () {
    expect(class_exists(ServeCommand::class))->toBeTrue();
});

it('builds php built-in server command with document root and router', function () {
    $server = new DevelopmentServer(
        host: '127.0.0.1',
        explicitPort: 8080,
        maxTries: 3,
        noReload: true,
        documentRoot: PINOOX_BASE_PATH,
        routerScript: DevelopmentServer::defaultRouterScript(),
        output: new Symfony\Component\Console\Output\BufferedOutput(),
    );

    expect($server->serverCommand())->toBe([
        DevelopmentServer::phpBinary(),
        '-S',
        '127.0.0.1:8080',
        '-t',
        PINOOX_BASE_PATH,
        DevelopmentServer::defaultRouterScript(),
    ])->and($server->url())->toBe('http://127.0.0.1:8080');
});

it('routes existing files through the development server router script', function () {
    $router = PINOOX_BASE_PATH . '/launcher/server.php';

    expect(is_file($router))->toBeTrue();

    $contents = file_get_contents($router);

    expect($contents)
        ->toContain('return false')
        ->toContain('index.php');
});

it('allows a project-level server.php override', function () {
    $default = DevelopmentServer::defaultRouterScript();

    expect($default)->toEndWith('/launcher/server.php');
});

it('is listed in pinoox command registry', function () {
    $application = new Symfony\Component\Console\Application();
    $application->add(new ServeCommand());

    expect($application->has('serve'))->toBeTrue();
});

