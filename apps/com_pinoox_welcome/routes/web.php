<?php

use function Pinoox\Router\get;

get('/', '@welcome')->name('home');
get('/file/loader', '@pinooxjs')->name('pinooxjs');
get('*', fn () => redirect(url('/')))->name('fallback');
