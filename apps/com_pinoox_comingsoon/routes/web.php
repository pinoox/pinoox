<?php

use App\com_pinoox_comingsoon\Controller\MainController;
use function Pinoox\Router\{get, post};

get('/', [MainController::class, 'main'])->name('home');
get('/panel', [MainController::class, 'panel'])->name('panel');
post('/panel', [MainController::class, 'save'])->name('panel.save');
get('*', fn () => redirect(url('/')))->name('fallback');
