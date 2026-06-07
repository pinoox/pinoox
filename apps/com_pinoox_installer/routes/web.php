<?php

use function Pinoox\Router\get;

get('/user', fn () => redirect('db'))->name('user.redirect');
get('/dist/pinoox.js', '@pinooxjs')->name('pinooxjs');
get('*', '@home')->name('home');
