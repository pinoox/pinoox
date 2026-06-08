<?php

use function Pinoox\Router\get;

get('/', '@welcome')->name('home');
get('*', fn () => redirect(url('/')))->name('fallback');
