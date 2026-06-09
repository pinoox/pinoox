<?php
// Theme helper functions

use Pinoox\Component\File;
use Pinoox\Component\Helpers\Str;

if (!function_exists('assets')) {
    function assets(string $path = '', bool $isPath = false): string {
        $assetsPath = File::name($path);
        return $path;
    }
} 