<?php

/**
 * Fallback platform requirements when no composer.json is readable yet.
 * Kept in launcher/ so pre-install checks work without pincore or vendor.
 */
return [
    'php' => '8.1.0',
    'extensions' => [
        'mysqli' => '*',
        'zip' => '*',
    ],
];
