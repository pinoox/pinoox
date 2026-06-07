<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default timezone
    |--------------------------------------------------------------------------
    */
    'timezone' => env('DATE_TIMEZONE', env('PINOOX_TIMEZONE', 'Asia/Tehran')),

    /*
    |--------------------------------------------------------------------------
    | Default calendar
    |--------------------------------------------------------------------------
    |
    | jalali | gregorian
    | When locale is fa and no override exists, jalali is used automatically.
    |
    */
    'calendar' => env('DATE_CALENDAR', 'jalali'),

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
    | Locale hints
    |--------------------------------------------------------------------------
    */
    'locale_calendar' => [
        'fa' => 'jalali',
        'en' => 'gregorian',
    ],
];
