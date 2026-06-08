<?php

use Pinoox\Cron\CronExpression;
use Pinoox\Cron\ScheduleRegistry;
use Pinoox\Cron\ScheduleRunner;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Terminal\Schedule\ScheduleListCommand;
use Pinoox\Terminal\Schedule\ScheduleRunCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    scheduleSystemDeleteTestApp('com_test_schedule');
    @unlink(scheduleSystemMarkerFile());
    AppEngine::__rebuild();
});

afterEach(function () {
    scheduleSystemDeleteTestApp('com_test_schedule');
    @unlink(scheduleSystemMarkerFile());
    AppEngine::__rebuild();
});

it('evaluates cron expressions for common schedule frequencies', function () {
    $date = new DateTimeImmutable('2026-06-06 02:15:00');

    expect((new CronExpression('* * * * *'))->isDue($date))->toBeTrue()
        ->and((new CronExpression('*/5 * * * *'))->isDue($date))->toBeTrue()
        ->and((new CronExpression('0 2 * * *'))->isDue($date))->toBeFalse()
        ->and((new CronExpression('15 2 * * *'))->isDue($date))->toBeTrue()
        ->and((new CronExpression('15 2 * * 6'))->isDue($date))->toBeTrue();
});

it('discovers scheduled tasks from app schedule files', function () {
    scheduleSystemWriteTestApp('com_test_schedule', <<<'PHP'
<?php

use Pinoox\Cron\Schedule;

return function (Schedule $schedule): void {
    $schedule->command('cache:clear')
        ->dailyAt('02:15')
        ->name('clear-cache')
        ->description('Clear cache')
        ->flow('maintenance')
        ->withoutOverlapping();
};
PHP);
    AppEngine::__rebuild();

    $tasks = (new ScheduleRegistry())->all('com_test_schedule');
    $task = $tasks[0]->toArray();

    expect($tasks)->toHaveCount(1)
        ->and($task['package'])->toBe('com_test_schedule')
        ->and($task['name'])->toBe('clear-cache')
        ->and($task['expression'])->toBe('15 2 * * *')
        ->and($task['flow'])->toBe(['maintenance'])
        ->and($task['without_overlapping'])->toBeTrue();
});

it('runs due scheduled callback tasks', function () {
    $marker = addslashes(scheduleSystemMarkerFile());
    scheduleSystemWriteTestApp('com_test_schedule', <<<PHP
<?php

use Pinoox\Cron\Schedule;

return function (Schedule \$schedule): void {
    \$schedule->call(function () {
        file_put_contents('{$marker}', 'ran');
    })->dailyAt('02:15')->name('write-marker');
};
PHP);
    AppEngine::__rebuild();

    $results = (new ScheduleRunner())->run(
        package: 'com_test_schedule',
        all: false,
        date: new DateTimeImmutable('2026-06-06 02:15:00')
    );

    expect($results)->toHaveCount(1)
        ->and($results[0]->isSuccess())->toBeTrue()
        ->and(file_get_contents(scheduleSystemMarkerFile()))->toBe('ran');
});

it('lists scheduled tasks through the cli command', function () {
    scheduleSystemWriteTestApp('com_test_schedule', <<<'PHP'
<?php

use Pinoox\Cron\Schedule;

return function (Schedule $schedule): void {
    $schedule->shell('echo hello')->hourly()->name('hello');
};
PHP);
    AppEngine::__rebuild();

    $tester = new CommandTester(new ScheduleListCommand());
    $tester->execute(['package' => 'com_test_schedule']);

    expect($tester->getStatusCode())->toBe(0)
        ->and($tester->getDisplay())->toContain('com_test_schedule')
        ->and($tester->getDisplay())->toContain('hello')
        ->and($tester->getDisplay())->toContain('0 * * * *');
});

it('runs matching tasks through the cli command with dry-run support', function () {
    scheduleSystemWriteTestApp('com_test_schedule', <<<'PHP'
<?php

use Pinoox\Cron\Schedule;

return function (Schedule $schedule): void {
    $schedule->shell('echo hello')->daily()->name('hello');
};
PHP);
    AppEngine::__rebuild();

    $application = new Application();
    $application->add(new ScheduleRunCommand());
    $command = $application->find('schedule:run');
    $tester = new CommandTester($command);
    $tester->execute([
        'package' => 'com_test_schedule',
        '--all' => true,
        '--dry-run' => true,
    ]);

    expect($tester->getStatusCode())->toBe(0)
        ->and($tester->getDisplay())->toContain('hello')
        ->and($tester->getDisplay())->toContain('skipped')
        ->and($tester->getDisplay())->toContain('Dry run');
});

function scheduleSystemWriteTestApp(string $package, string $schedule): void
{
    $dir = testProjectRoot() . '/apps/' . $package;

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($dir . '/app.php', "<?php\n\nreturn ['package' => '{$package}', 'enable' => true, 'name' => '{$package}'];\n");
    file_put_contents($dir . '/schedule.php', $schedule);
}

function scheduleSystemDeleteTestApp(string $package): void
{
    scheduleSystemDeleteDirectory(testProjectRoot() . '/apps/' . $package);
    scheduleSystemDeleteDirectory(testProjectRoot() . '/pinker/apps/' . $package);
}

function scheduleSystemDeleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}

function scheduleSystemMarkerFile(): string
{
    return testFixtures('schedule-marker.txt');
}

