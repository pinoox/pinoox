<?php

use App\com_pinoox_installer\Controller\MainController;
use function Pinoox\Router\action;

action('home', [MainController::class, 'home']);
