<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

use App\com_pinoox_manager\Controller\AccountController;
use App\com_pinoox_manager\Controller\RouterController;
use App\com_pinoox_manager\Controller\UserController;
use App\com_pinoox_manager\Controller\AppController;
use App\com_pinoox_manager\Controller\AuthController;
use App\com_pinoox_manager\Controller\OptionController;
use App\com_pinoox_manager\Controller\WidgetController;
use function Pinoox\Router\{get, post};

// User
get(
    path: '/user/logout',
    action: [AuthController::class, 'logout'],
);

get(
    path: '/user/lock',
    action: [AuthController::class, 'lock'],
);

// App
get(
    path: 'app/get/{filter}',
    action: [AppController::class, 'get'],
    defaults: [
        'filter' => null,
    ]
);

get(
    path: 'app/getConfig/{packageName}',
    action: [AppController::class, 'getConfig']
);

get(
    path: 'app/setConfig/{packageName}/{key}',
    action: [AppController::class, 'setConfig']
);

// Widget
get(
    path: 'widget/clock',
    action: [WidgetController::class, 'clock'],
);

get(
    path: 'widget/storage',
    action: [WidgetController::class, 'storage'],
);

// Account
get(
    path: 'account/getPinooxAuth',
    action: [AccountController::class, 'getPinooxAuth'],
);

get(
    path: 'account/connect',
    action: [AccountController::class, 'connect'],
);

get(
    path: 'account/getConnectData',
    action: [AccountController::class, 'getConnectData'],
);

get(
    path: 'account/logout',
    action: [AccountController::class, 'logout'],
);

// Option

get(
    path: 'options/changeBackground/{name}',
    action: [OptionController::class, 'changeBackground']
);

get(
    path: 'options/changeLockTime/{minutes}',
    action: [OptionController::class, 'changeLockTime'],
    defaults: [
        'minutes' => 0,
    ]
);

get(
    path: 'changeLang/{lang}',
    action: [OptionController::class, 'changeLang']
);


// User
post(
    path: 'user/changeAvatar/',
    action: [UserController::class, 'changeAvatar']
);

get(
    path: 'user/deleteAvatar',
    action: [UserController::class, 'deleteAvatar']
);

post(
    path: 'user/changeInfo/',
    action: [UserController::class, 'changeInfo']
);

post(
    path: 'user/changePassword/',
    action: [UserController::class, 'changePassword']
);

// Router

get(
    path: 'router/get',
    action: [RouterController::class, 'get']
);

post(
    path: 'router/add',
    action: [RouterController::class, 'add']
);

post(
    path: 'router/remove',
    action: [RouterController::class, 'remove']
);

post(
    path: 'router/setPackageName',
    action: [RouterController::class, 'setPackageName']
);