<?php

/**
 * @license  https://opensource.org/licenses/MIT MIT License
 */

use App\com_pinoox_manager\Controller\AuthController;
use App\com_pinoox_manager\Controller\UserController;
use App\com_pinoox_manager\Controller\OptionController;
use App\com_pinoox_manager\Controller\WidgetController;
use App\com_pinoox_manager\Controller\AppController;
use App\com_pinoox_manager\Controller\RouterController;
use App\com_pinoox_manager\Controller\AccountController;
use App\com_pinoox_manager\Controller\MarketController;
use App\com_pinoox_manager\Controller\NotificationController;
use App\com_pinoox_manager\Controller\UpdateController;
use App\com_pinoox_manager\Controller\TemplateController;
use App\com_pinoox_manager\Controller\PinionController;

return [
    'flow' => ['manager.auth'],
    'routes' => [
        [
            'prefix' => '/auth',
            'as' => 'auth.',
            'controller' => AuthController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/lock', 'action' => 'lock', 'name' => 'lock'],
                [
                    'method' => 'POST',
                    'uri' => '/unlock',
                    'action' => 'unlock',
                    'name' => 'unlock',
                    'tag' => 'Authentication',
                    'summary' => 'Unlock screen',
                    'description' => 'Unlock the manager session after screen lock using the account password.',
                ],
            ],
        ],
        [
            'prefix' => '/user',
            'as' => 'user.',
            'controller' => UserController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/get', 'action' => 'get', 'name' => 'get'],
                ['method' => 'GET', 'uri' => '/getOptions', 'action' => 'getOptions', 'name' => 'getOptions'],
                ['method' => 'GET', 'uri' => '/deleteAvatar', 'action' => 'deleteAvatar', 'name' => 'deleteAvatar'],
                ['method' => 'POST', 'uri' => '/changeAvatar', 'action' => 'changeAvatar', 'name' => 'changeAvatar'],
                ['method' => 'POST', 'uri' => '/changeInfo', 'action' => 'changeInfo', 'name' => 'changeInfo'],
                ['method' => 'POST', 'uri' => '/changePassword', 'action' => 'changePassword', 'name' => 'changePassword'],
                [
                    'method' => 'GET',
                    'uri' => '/getUsers/{packageName}',
                    'action' => 'getUsers',
                    'name' => 'getUsers.packageName',
                    'permission' => 'manager.users.view',
                ],
            ],
        ],
        [
            'prefix' => '/options',
            'as' => 'options.',
            'controller' => OptionController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/get', 'action' => 'getOptions', 'name' => 'get'],
                ['method' => 'GET', 'uri' => '/changeBackground/{name}', 'action' => 'changeBackground', 'name' => 'changeBackground.name'],
                ['method' => 'POST', 'uri' => '/uploadWallpaper', 'action' => 'uploadWallpaper', 'name' => 'uploadWallpaper'],
                ['method' => 'POST', 'uri' => '/deleteWallpaper/{name}', 'action' => 'deleteWallpaper', 'name' => 'deleteWallpaper.name'],
                ['method' => 'GET', 'uri' => '/changeLockTime/{minutes}', 'action' => 'changeLockTime', 'name' => 'changeLockTime.minutes'],
                ['method' => 'GET', 'uri' => '/toggleDockPin/{packageName}', 'action' => 'toggleDockPin', 'name' => 'toggleDockPin.packageName'],
                ['method' => 'GET', 'uri' => '/changeAppViewMode/{mode}', 'action' => 'changeAppViewMode', 'name' => 'changeAppViewMode.mode'],
            ],
        ],
        [
            'method' => 'GET',
            'uri' => '/changeLang/{lang}',
            'action' => [OptionController::class, 'changeLang'],
            'name' => 'changeLang.lang',
        ],
        [
            'prefix' => '/widget',
            'as' => 'widget.',
            'controller' => WidgetController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/clock', 'action' => 'clock', 'name' => 'clock'],
                ['method' => 'GET', 'uri' => '/storage', 'action' => 'storage', 'name' => 'storage'],
                ['method' => 'GET', 'uri' => '/storageBrowse', 'action' => 'browseStorage', 'name' => 'storageBrowse'],
                ['method' => 'GET', 'uri' => '/settings', 'action' => 'settings', 'name' => 'settings'],
                ['method' => 'POST', 'uri' => '/saveWidgets', 'action' => 'saveWidgets', 'name' => 'saveWidgets'],
                ['method' => 'POST', 'uri' => '/storageSettings', 'action' => 'saveStorageSettings', 'name' => 'storageSettings'],
            ],
        ],
        [
            'prefix' => '/app',
            'as' => 'app.',
            'controller' => AppController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/iconPack', 'action' => 'iconPack', 'name' => 'iconPack'],
                ['method' => 'GET', 'uri' => '/getAll', 'action' => 'getAll', 'name' => 'getAll'],
                ['method' => 'GET', 'uri' => '/get/{filter?}', 'action' => 'get', 'name' => 'get.filter'],
                ['method' => 'GET', 'uri' => '/getConfig/{packageName}', 'action' => 'getConfig', 'name' => 'getConfig.packageName'],
                ['method' => 'POST', 'uri' => '/setConfig/{packageName}/{key}', 'action' => 'setConfig', 'name' => 'setConfig.packageName.key'],
                ['method' => 'POST', 'uri' => '/install', 'action' => 'install', 'name' => 'install'],
                ['method' => 'GET', 'uri' => '/packageMeta/{filename}', 'action' => 'packageMeta', 'name' => 'packageMeta.filename'],
                ['method' => 'GET', 'uri' => '/installPackage/{filename}', 'action' => 'installPackage', 'name' => 'installPackage.filename'],
                ['method' => 'POST', 'uri' => '/installPackage/start', 'action' => 'installPackageStart', 'name' => 'installPackage.start'],
                ['method' => 'GET', 'uri' => '/installPackage/status/{installId}', 'action' => 'installPackageStatus', 'name' => 'installPackage.status'],
                ['method' => 'POST', 'uri' => '/database/checkPrefix', 'action' => 'checkDatabasePrefix', 'name' => 'database.checkPrefix'],
                ['method' => 'POST', 'uri' => '/database/testConnection', 'action' => 'testDatabaseConnection', 'name' => 'database.testConnection'],
                ['method' => 'GET', 'uri' => '/database/defaults', 'action' => 'databaseDefaults', 'name' => 'database.defaults'],
                ['method' => 'GET', 'uri' => '/files', 'action' => 'files', 'name' => 'files'],
                ['method' => 'POST', 'uri' => '/deleteFile', 'action' => 'deleteFile', 'name' => 'deleteFile'],
                ['method' => 'POST', 'uri' => '/filesUpload', 'action' => 'filesUpload', 'name' => 'filesUpload'],
                [
                    'prefix' => '/pinion',
                    'as' => 'pinion.',
                    'controller' => PinionController::class,
                    'routes' => [
                        ['method' => 'GET', 'uri' => '/limits', 'action' => 'limits', 'name' => 'limits'],
                        ['method' => 'POST', 'uri' => '/init', 'action' => 'init', 'name' => 'init'],
                        ['method' => 'POST', 'uri' => '/upload', 'action' => 'upload', 'name' => 'upload'],
                        ['method' => 'POST', 'uri' => '/complete', 'action' => 'complete', 'name' => 'complete'],
                        ['method' => 'GET', 'uri' => '/status/{uploadId}', 'action' => 'status', 'name' => 'status'],
                        ['method' => 'POST', 'uri' => '/abort/{uploadId}', 'action' => 'abort', 'name' => 'abort'],
                    ],
                ],
                ['method' => 'POST', 'uri' => '/remove/{packageName}', 'action' => 'remove', 'name' => 'remove.packageName'],
            ],
        ],
        [
            'prefix' => '/router',
            'as' => 'router.',
            'controller' => RouterController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/getAll', 'action' => 'getAll', 'name' => 'getAll'],
                ['method' => 'POST', 'uri' => '/remove', 'action' => 'remove', 'name' => 'remove'],
                ['method' => 'POST', 'uri' => '/save', 'action' => 'save', 'name' => 'save'],
            ],
        ],
        [
            'prefix' => '/account',
            'as' => 'account.',
            'controller' => AccountController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/getConnectData', 'action' => 'getConnectData', 'name' => 'getConnectData'],
                ['method' => 'GET', 'uri' => '/connect', 'action' => 'connect', 'name' => 'connect'],
                ['method' => 'GET', 'uri' => '/logout', 'action' => 'logout', 'name' => 'logout'],
            ],
        ],
        [
            'prefix' => '/market',
            'as' => 'market.',
            'controller' => MarketController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/getDownloads', 'action' => 'getDownloads', 'name' => 'getDownloads'],
                ['method' => 'POST', 'uri' => '/deleteDownload', 'action' => 'deleteDownload', 'name' => 'deleteDownload'],
                ['method' => 'GET', 'uri' => '/getApps/{keyword?}', 'action' => 'getApps', 'name' => 'getApps.keyword'],
                ['method' => 'GET', 'uri' => '/getOneApp/{package_name}', 'action' => 'getOneApp', 'name' => 'getOneApp.package_name'],
                ['method' => 'POST', 'uri' => '/downloadRequest/{package_name}', 'action' => 'downloadRequest', 'name' => 'downloadRequest.package_name'],
                ['method' => 'GET', 'uri' => '/getTemplates/{package_name}', 'action' => 'getTemplates', 'name' => 'getTemplates.package_name'],
                ['method' => 'POST', 'uri' => '/downloadRequestTemplate/{uid}', 'action' => 'downloadRequestTemplate', 'name' => 'downloadRequestTemplate.uid'],
            ],
        ],
        [
            'prefix' => '/notification',
            'as' => 'notification.',
            'controller' => NotificationController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '', 'action' => 'index', 'name' => ''],
                ['method' => 'POST', 'uri' => '/hide', 'action' => 'hide', 'name' => 'hide'],
                ['method' => 'POST', 'uri' => '/seen', 'action' => 'seen', 'name' => 'seen'],
            ],
        ],
        [
            'prefix' => '/update',
            'as' => 'update.',
            'controller' => UpdateController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/checkVersion/{type?}', 'action' => 'checkVersion', 'name' => 'checkVersion.type'],
                ['method' => 'GET', 'uri' => '/install', 'action' => 'install', 'name' => 'install'],
            ],
        ],
        [
            'prefix' => '/template',
            'as' => 'template.',
            'controller' => TemplateController::class,
            'routes' => [
                ['method' => 'GET', 'uri' => '/get/{packageName}', 'action' => 'get', 'name' => 'get.packageName'],
                ['method' => 'GET', 'uri' => '/install/{uid}/{packageName}', 'action' => 'install', 'name' => 'install.uid.packageName'],
                ['method' => 'GET', 'uri' => '/installPackage/{filename}', 'action' => 'installPackage', 'name' => 'installPackage.filename'],
                ['method' => 'GET', 'uri' => '/set/{packageName}/{folderName}', 'action' => 'set', 'name' => 'set.packageName.folderName'],
                ['method' => 'GET', 'uri' => '/remove/{packageName}/{folderName}', 'action' => 'remove', 'name' => 'remove.packageName.folderName'],
            ],
        ],
    ],
];
