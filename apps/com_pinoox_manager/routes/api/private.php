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
use function Pinoox\Router\{collect, get, group, post};

return collect(['flow' => ['manager.auth']], function () {
    group('/auth')
        ->as('auth.')
        ->controller(AuthController::class)
        ->routes(function () {
            get('/lock', 'lock')->name('lock');
            post('/unlock', 'unlock')->name('unlock');
        });

    group('/user')
        ->as('user.')
        ->controller(UserController::class)
        ->routes(function () {
            get('/get', 'get')->name('get');
            get('/getOptions', 'getOptions')->name('getOptions');
            get('/deleteAvatar', 'deleteAvatar')->name('deleteAvatar');
            post('/changeAvatar', 'changeAvatar')->name('changeAvatar');
            post('/changeInfo', 'changeInfo')->name('changeInfo');
            post('/changePassword', 'changePassword')->name('changePassword');
            get('/getUsers/{packageName}', 'getUsers')->name('getUsers.packageName');
        });

    group('/options')
        ->as('options.')
        ->controller(OptionController::class)
        ->routes(function () {
            get('/get', 'getOptions')->name('get');
            get('/changeBackground/{name}', 'changeBackground')->name('changeBackground.name');
            post('/uploadWallpaper', 'uploadWallpaper')->name('uploadWallpaper');
            post('/deleteWallpaper/{name}', 'deleteWallpaper')->name('deleteWallpaper.name');
            get('/changeLockTime/{minutes}', 'changeLockTime')->name('changeLockTime.minutes');
            get('/toggleDockPin/{packageName}', 'toggleDockPin')->name('toggleDockPin.packageName');
            get('/changeAppViewMode/{mode}', 'changeAppViewMode')->name('changeAppViewMode.mode');
        });

    get('/changeLang/{lang}', [OptionController::class, 'changeLang'])->name('changeLang.lang');

    group('/widget')
        ->as('widget.')
        ->controller(WidgetController::class)
        ->routes(function () {
            get('/clock', 'clock')->name('clock');
            get('/storage', 'storage')->name('storage');
            get('/storageBrowse', 'browseStorage')->name('storageBrowse');
            get('/settings', 'settings')->name('settings');
            post('/saveWidgets', 'saveWidgets')->name('saveWidgets');
            post('/storageSettings', 'saveStorageSettings')->name('storageSettings');
        });

    group('/app')
        ->as('app.')
        ->controller(AppController::class)
        ->routes(function () {
            get('/iconPack', 'iconPack')->name('iconPack');
            get('/getAll', 'getAll')->name('getAll');
            get('/get/{filter?}', 'get')->name('get.filter');
            get('/getConfig/{packageName}', 'getConfig')->name('getConfig.packageName');
            post('/setConfig/{packageName}/{key}', 'setConfig')->name('setConfig.packageName.key');
            post('/install', 'install')->name('install');
            get('/packageMeta/{filename}', 'packageMeta')->name('packageMeta.filename');
            get('/installPackage/{filename}', 'installPackage')->name('installPackage.filename');
            post('/installPackage/start', 'installPackageStart')->name('installPackage.start');
            get('/installPackage/status/{installId}', 'installPackageStatus')->name('installPackage.status');
            post('/database/checkPrefix', 'checkDatabasePrefix')->name('database.checkPrefix');
            post('/database/testConnection', 'testDatabaseConnection')->name('database.testConnection');
            get('/database/defaults', 'databaseDefaults')->name('database.defaults');
            get('/files', 'files')->name('files');
            post('/deleteFile', 'deleteFile')->name('deleteFile');
            post('/filesUpload', 'filesUpload')->name('filesUpload');

            group('/pinion')
                ->as('pinion.')
                ->controller(PinionController::class)
                ->routes(function () {
                    get('/limits', 'limits')->name('limits');
                    post('/init', 'init')->name('init');
                    post('/upload', 'upload')->name('upload');
                    post('/complete', 'complete')->name('complete');
                    get('/status/{uploadId}', 'status')->name('status');
                    post('/abort/{uploadId}', 'abort')->name('abort');
                });

            post('/remove/{packageName}', 'remove')->name('remove.packageName');
        });

    group('/router')
        ->as('router.')
        ->controller(RouterController::class)
        ->routes(function () {
            get('/getAll', 'getAll')->name('getAll');
            post('/remove', 'remove')->name('remove');
            post('/save', 'save')->name('save');
        });

    group('/account')
        ->as('account.')
        ->controller(AccountController::class)
        ->routes(function () {
            get('/getConnectData', 'getConnectData')->name('getConnectData');
            get('/connect', 'connect')->name('connect');
            get('/logout', 'logout')->name('logout');
        });

    group('/market')
        ->as('market.')
        ->controller(MarketController::class)
        ->routes(function () {
            get('/getDownloads', 'getDownloads')->name('getDownloads');
            post('/deleteDownload', 'deleteDownload')->name('deleteDownload');
            get('/getApps/{keyword?}', 'getApps')->name('getApps.keyword');
            get('/getOneApp/{package_name}', 'getOneApp')->name('getOneApp.package_name');
            post('/downloadRequest/{package_name}', 'downloadRequest')->name('downloadRequest.package_name');
            get('/getTemplates/{package_name}', 'getTemplates')->name('getTemplates.package_name');
            post('/downloadRequestTemplate/{uid}', 'downloadRequestTemplate')->name('downloadRequestTemplate.uid');
        });

    group('/notification')
        ->as('notification.')
        ->controller(NotificationController::class)
        ->routes(function () {
            get('', 'index')->name('');
            post('/hide', 'hide')->name('hide');
            post('/seen', 'seen')->name('seen');
        });

    group('/update')
        ->as('update.')
        ->controller(UpdateController::class)
        ->routes(function () {
            get('/checkVersion/{type?}', 'checkVersion')->name('checkVersion.type');
            get('/install', 'install')->name('install');
        });

    group('/template')
        ->as('template.')
        ->controller(TemplateController::class)
        ->routes(function () {
            get('/get/{packageName}', 'get')->name('get.packageName');
            get('/install/{uid}/{packageName}', 'install')->name('install.uid.packageName');
            get('/installPackage/{filename}', 'installPackage')->name('installPackage.filename');
            get('/set/{packageName}/{folderName}', 'set')->name('set.packageName.folderName');
            get('/remove/{packageName}/{folderName}', 'remove')->name('remove.packageName.folderName');
        });
});
