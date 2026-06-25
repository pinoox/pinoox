<?php

use App\com_pinoox_app\Controller\MainController;
use App\com_pinoox_app\Router\Actions;
use function Pinoox\Router\action;

action(Actions::HOME, [MainController::class, 'index']);
