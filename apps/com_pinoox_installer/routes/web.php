<?php
use function Pinoox\Router\get;
get('/user', fn () => redirect('db'))->name('user.redirect');
get('*', '@home')->name('home');
