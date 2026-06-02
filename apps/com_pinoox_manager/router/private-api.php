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
use App\com_pinoox_manager\Controller\AppController;
use App\com_pinoox_manager\Controller\AuthController;
use App\com_pinoox_manager\Controller\MarketController;
use App\com_pinoox_manager\Controller\NotificationController;
use App\com_pinoox_manager\Controller\OptionController;
use App\com_pinoox_manager\Controller\RouterController;
use App\com_pinoox_manager\Controller\TemplateController;
use App\com_pinoox_manager\Controller\UpdateController;
use App\com_pinoox_manager\Controller\UserController;
use App\com_pinoox_manager\Controller\WidgetController;
use function Pinoox\Router\{get, post};

// auth
get(path: 'auth/lock', action: [AuthController::class, 'lock']);

// user
get(path: 'user/get', action: [UserController::class, 'get']);
get(path: 'user/getOptions', action: [UserController::class, 'getOptions']);
get(path: 'user/deleteAvatar', action: [UserController::class, 'deleteAvatar']);
post(path: 'user/changeAvatar', action: [UserController::class, 'changeAvatar']);
post(path: 'user/changeInfo', action: [UserController::class, 'changeInfo']);
post(path: 'user/changePassword', action: [UserController::class, 'changePassword']);
get(path: 'user/getUsers/{packageName}', action: [UserController::class, 'getUsers']);

// options
get(path: 'options/get', action: [OptionController::class, 'getOptions']);
get(path: 'options/changeBackground/{name}', action: [OptionController::class, 'changeBackground']);
post(path: 'options/uploadWallpaper', action: [OptionController::class, 'uploadWallpaper']);
post(path: 'options/deleteWallpaper/{name}', action: [OptionController::class, 'deleteWallpaper']);
get(path: 'options/changeLockTime/{minutes}', action: [OptionController::class, 'changeLockTime']);
get(path: 'options/toggleDockPin/{packageName}', action: [OptionController::class, 'toggleDockPin']);
get(path: 'changeLang/{lang}', action: [OptionController::class, 'changeLang']);

// widgets
get(path: 'widget/clock', action: [WidgetController::class, 'clock']);
get(path: 'widget/storage', action: [WidgetController::class, 'storage']);
get(path: 'widget/storageBrowse', action: [WidgetController::class, 'browseStorage']);
get(path: 'widget/settings', action: [WidgetController::class, 'settings']);
post(path: 'widget/saveWidgets', action: [WidgetController::class, 'saveWidgets']);
post(path: 'widget/storageSettings', action: [WidgetController::class, 'saveStorageSettings']);

// apps
get(path: 'app/getAll', action: [AppController::class, 'getAll']);
get(path: 'app/get/{filter?}', action: [AppController::class, 'get']);
get(path: 'app/getConfig/{packageName}', action: [AppController::class, 'getConfig']);
post(path: 'app/setConfig/{packageName}/{key}', action: [AppController::class, 'setConfig']);
post(path: 'app/install', action: [AppController::class, 'install']);
get(path: 'app/installPackage/{filename}', action: [AppController::class, 'installPackage']);
get(path: 'app/files', action: [AppController::class, 'files']);
post(path: 'app/deleteFile', action: [AppController::class, 'deleteFile']);
post(path: 'app/filesUpload', action: [AppController::class, 'filesUpload']);
post(path: 'app/remove/{packageName}', action: [AppController::class, 'remove']);

// router
get(path: 'router/getAll', action: [RouterController::class, 'getAll']);
post(path: 'router/remove', action: [RouterController::class, 'remove']);
post(path: 'router/save', action: [RouterController::class, 'save']);

// account (pinoox cloud)
get(path: 'account/getConnectData', action: [AccountController::class, 'getConnectData']);
get(path: 'account/connect', action: [AccountController::class, 'connect']);
get(path: 'account/logout', action: [AccountController::class, 'logout']);

// market
get(path: 'market/getDownloads', action: [MarketController::class, 'getDownloads']);
post(path: 'market/deleteDownload', action: [MarketController::class, 'deleteDownload']);
get(path: 'market/getApps/{keyword?}', action: [MarketController::class, 'getApps']);
get(path: 'market/getOneApp/{package_name}', action: [MarketController::class, 'getOneApp']);
post(path: 'market/downloadRequest/{package_name}', action: [MarketController::class, 'downloadRequest']);
get(path: 'market/getTemplates/{package_name}', action: [MarketController::class, 'getTemplates']);
post(path: 'market/downloadRequestTemplate/{uid}', action: [MarketController::class, 'downloadRequestTemplate']);

// notifications
get(path: 'notification', action: [NotificationController::class, 'index']);
post(path: 'notification/hide', action: [NotificationController::class, 'hide']);
post(path: 'notification/seen', action: [NotificationController::class, 'seen']);

// update
get(path: 'update/checkVersion/{type?}', action: [UpdateController::class, 'checkVersion']);
get(path: 'update/install', action: [UpdateController::class, 'install']);

// templates
get(path: 'template/get/{packageName}', action: [TemplateController::class, 'get']);
get(path: 'template/install/{uid}/{packageName}', action: [TemplateController::class, 'install']);
get(path: 'template/installPackage/{filename}', action: [TemplateController::class, 'installPackage']);
get(path: 'template/set/{packageName}/{folderName}', action: [TemplateController::class, 'set']);
get(path: 'template/remove/{packageName}/{folderName}', action: [TemplateController::class, 'remove']);
