<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default timezone
    |--------------------------------------------------------------------------
    |
    | International default is UTC. Override per app in app.php → date.timezone
    | or via DATE_TIMEZONE in .env.
    |
    */
    'timezone' => env('DATE_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Default calendar
    |--------------------------------------------------------------------------
    |
    | jalali | gregorian
    |
    | Platform default is gregorian. Per-app override: app.php → date.calendar.
    | When an app leaves date.calendar empty, locale_calendar below is used.
    |
    */
    'calendar' => env('DATE_CALENDAR', 'gregorian'),

    /*
    |--------------------------------------------------------------------------
    | Display formats
    |--------------------------------------------------------------------------
    */
    'formats' => [
        'jalali' => [
            'date' => 'Y/m/d',
            'datetime' => 'Y/m/d H:i',
            'time' => 'H:i',
            'full' => 'l d F Y',
        ],
        'gregorian' => [
            'date' => 'Y-m-d',
            'datetime' => 'Y-m-d H:i',
            'time' => 'H:i',
            'full' => 'l d F Y',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale calendar hints
    |--------------------------------------------------------------------------
    |
    | Used when app.php → date.calendar is not set. Explicit app or code
    | overrides always win (see DateManager::calendar()).
    |
    */
    'locale_calendar' => [
        'fa' => 'jalali',
        'en' => 'gregorian',
    ],
];
