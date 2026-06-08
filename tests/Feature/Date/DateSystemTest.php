<?php

use Pinoox\Component\Date\DateManager;
use Pinoox\Component\Date\JalaliDate;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Date;

beforeEach(function () {
    Loader::setBasePath(dirname(__DIR__, 3));
    AppProvider::___();
});

it('declares the Date portal contract', function () {
    expectPortalContract(Date::class);
});

it('forwards carbon factory calls', function () {
    expect(Date::___())->toBeInstanceOf(DateManager::class)
        ->and(Date::parse('2024-01-02')->format('Y-m-d'))->toBe('2024-01-02');
});

it('formats jalali dates through morilog', function () {
    $jalali = Date::jalali('2024-01-02');

    expect($jalali)->toBeInstanceOf(JalaliDate::class)
        ->and($jalali->format('Y/m/d'))->toBe('1402/10/12');
});

it('formats jalali dates through the jalali() API', function () {
    expect(Date::jalali('2024-01-02')->format('Y/m/d'))->toBe('1402/10/12');
});

it('parses jalali input to gregorian dates', function () {
    expect(Date::parseJalali('1402-10-12', 'Y-m-d')->toCarbon()->format('Y-m-d'))->toBe('2024-01-02');
});

it('provides smart formatting based on calendar config', function () {
    expect(Date::format('2024-01-02', 'Y/m/d', 'jalali'))->toBe('1402/10/12')
        ->and(Date::format('2024-01-02', 'Y-m-d', 'gregorian'))->toBe('2024-01-02');
});

it('defaults to gregorian platform calendar', function () {
    $manager = new DateManager(['calendar' => 'gregorian', 'locale_calendar' => ['fa' => 'jalali']]);

    expect($manager->calendar())->toBe('gregorian');
});

it('uses locale calendar hint when app calendar is not set', function () {
    $manager = new DateManager([
        'calendar' => 'gregorian',
        'locale_calendar' => ['fa' => 'jalali', 'en' => 'gregorian'],
    ]);

    expect($manager->usingCalendar('jalali')->format('2024-01-02', 'Y/m/d'))->toBe('1402/10/12')
        ->and($manager->calendar())->toBe('gregorian');
});

it('allows explicit calendar override via usingCalendar without mutating the portal', function () {
    $before = Date::calendar();

    expect(Date::usingCalendar('jalali')->format('2024-01-02', 'Y/m/d'))->toBe('1402/10/12')
        ->and(Date::calendar())->toBe($before);
});

it('exposes jformat helper', function () {
    expect(jformat('2024-01-02', 'Y/m/d'))->toBe('1402/10/12')
        ->and(format_jalali('2024-01-02', 'Y/m/d'))->toBe('1402/10/12');
});

