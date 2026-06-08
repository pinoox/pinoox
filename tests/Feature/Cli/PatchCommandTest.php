<?php

use Pinoox\Component\Database\Patch\PatchBase;
use Pinoox\Terminal\Patch\PatchCreateCommand;
use Pinoox\Terminal\Patch\PatchRollbackCommand;
use Pinoox\Terminal\Patch\PatchRunCommand;
use Pinoox\Terminal\Patch\PatchStatusCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

it('matches timestamped patch files by their short name', function () {
    $command = new PatchRollbackCommand();
    $method = new ReflectionMethod($command, 'matches');
    $method->setAccessible(true);

    expect($method->invoke($command, [
        'name' => '2026_06_06_085158_test',
        'class' => 'Pinoox\\Patches\\Patch@anonymous',
    ], 'test'))->toBeTrue()
        ->and($method->invoke($command, [
            'name' => '2026_06_06_085158_test',
            'class' => 'Pinoox\\Patches\\Patch@anonymous',
        ], '2026_06_06_085158_test'))->toBeTrue();
});

it('runs the new up method through the legacy run entrypoint', function () {
    if (!class_exists('PatchRunCompatibilityTestPatch')) {
        eval('class PatchRunCompatibilityTestPatch extends \Pinoox\Component\Database\Patch\PatchBase { public bool $executed = false; public function up(): void { $this->executed = true; } }');
    }

    $class = new ReflectionClass('PatchRunCompatibilityTestPatch');

    $patch = $class->newInstanceWithoutConstructor();

    $patch->run();

    expect($patch->executed)->toBeTrue();
});

it('requires interactive package selection for patch commands without package input', function () {
    foreach ([
        new PatchCreateCommand(),
        new PatchRunCommand(),
        new PatchRollbackCommand(),
        new PatchStatusCommand(),
    ] as $command) {
        $argument = $command->getDefinition()->getArgument('package');

        expect($argument->isRequired())->toBeFalse()
            ->and($argument->getDefault())->toBeNull();
    }
});

it('lets patch status select a package interactively by number', function () {
    $application = new Application();
    $application->add(new PatchStatusCommand());

    $tester = new CommandTester($application->find('patch:status'));
    $tester->setInputs(['0']);

    $status = $tester->execute([]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain('Available packages')
        ->and($tester->getDisplay())->toContain('platform')
        ->and($tester->getDisplay())->toContain('App')
        ->and($tester->getDisplay())->toContain('Patch');
});

