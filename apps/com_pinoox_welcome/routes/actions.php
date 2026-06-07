<?php
use App\com_pinoox_welcome\Controller\MainController;
use function Pinoox\Router\action;
action('welcome', [MainController::class, 'home']);
action('pinooxjs', [MainController::class, 'pinooxjs']);

