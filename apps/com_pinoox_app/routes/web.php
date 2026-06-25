<?php

use App\com_pinoox_app\Router\Actions;
use function Pinoox\Router\get;

get('/')->actionName(Actions::HOME);
