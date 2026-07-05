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
        'method' => 'GET',
        'uri' => '/auth/lock',
        'action' => [AuthController::class, 'lock'],
        'name' => 'auth.lock',
    ],
    [
        'method' => 'POST',
        'uri' => '/auth/unlock',
        'action' => [AuthController::class, 'unlock'],
        'name' => 'auth.unlock',
        'tag' => 'Authentication',
        'summary' => 'Unlock screen',
        'description' => 'Unlock the manager session after screen lock using the account password.',
    ],
    [
        'method' => 'GET',
        'uri' => '/user/get',
        'action' => [UserController::class, 'get'],
        'name' => 'user.get',
    ],
    [
        'method' => 'GET',
        'uri' => '/user/getOptions',
        'action' => [UserController::class, 'getOptions'],
        'name' => 'user.getOptions',
    ],
    [
        'method' => 'GET',
        'uri' => '/user/deleteAvatar',
        'action' => [UserController::class, 'deleteAvatar'],
        'name' => 'user.deleteAvatar',
    ],
    [
        'method' => 'POST',
        'uri' => '/user/changeAvatar',
        'action' => [UserController::class, 'changeAvatar'],
        'name' => 'user.changeAvatar',
    ],
    [
        'method' => 'POST',
        'uri' => '/user/changeInfo',
        'action' => [UserController::class, 'changeInfo'],
        'name' => 'user.changeInfo',
    ],
    [
        'method' => 'POST',
        'uri' => '/user/changePassword',
        'action' => [UserController::class, 'changePassword'],
        'name' => 'user.changePassword',
    ],
    [
        'method' => 'GET',
        'uri' => '/user/getUsers/{packageName}',
        'action' => [UserController::class, 'getUsers'],
        'name' => 'user.getUsers.packageName',
        'permission' => 'manager.users.view',
    ],
    [
        'method' => 'GET',
        'uri' => '/options/get',
        'action' => [OptionController::class, 'getOptions'],
        'name' => 'options.get',
    ],
    [
        'method' => 'GET',
        'uri' => '/options/changeBackground/{name}',
        'action' => [OptionController::class, 'changeBackground'],
        'name' => 'options.changeBackground.name',
    ],
    [
        'method' => 'POST',
        'uri' => '/options/uploadWallpaper',
        'action' => [OptionController::class, 'uploadWallpaper'],
        'name' => 'options.uploadWallpaper',
    ],
    [
        'method' => 'POST',
        'uri' => '/options/deleteWallpaper/{name}',
        'action' => [OptionController::class, 'deleteWallpaper'],
        'name' => 'options.deleteWallpaper.name',
    ],
    [
        'method' => 'GET',
        'uri' => '/options/changeLockTime/{minutes}',
        'action' => [OptionController::class, 'changeLockTime'],
        'name' => 'options.changeLockTime.minutes',
    ],
    [
        'method' => 'GET',
        'uri' => '/options/toggleDockPin/{packageName}',
        'action' => [OptionController::class, 'toggleDockPin'],
        'name' => 'options.toggleDockPin.packageName',
    ],
    [
        'method' => 'GET',
        'uri' => '/options/changeAppViewMode/{mode}',
        'action' => [OptionController::class, 'changeAppViewMode'],
        'name' => 'options.changeAppViewMode.mode',
    ],
    [
        'method' => 'GET',
        'uri' => '/changeLang/{lang}',
        'action' => [OptionController::class, 'changeLang'],
        'name' => 'changeLang.lang',
    ],
    [
        'method' => 'GET',
        'uri' => '/widget/clock',
        'action' => [WidgetController::class, 'clock'],
        'name' => 'widget.clock',
    ],
    [
        'method' => 'GET',
        'uri' => '/widget/storage',
        'action' => [WidgetController::class, 'storage'],
        'name' => 'widget.storage',
    ],
    [
        'method' => 'GET',
        'uri' => '/widget/storageBrowse',
        'action' => [WidgetController::class, 'browseStorage'],
        'name' => 'widget.storageBrowse',
    ],
    [
        'method' => 'GET',
        'uri' => '/widget/settings',
        'action' => [WidgetController::class, 'settings'],
        'name' => 'widget.settings',
    ],
    [
        'method' => 'POST',
        'uri' => '/widget/saveWidgets',
        'action' => [WidgetController::class, 'saveWidgets'],
        'name' => 'widget.saveWidgets',
    ],
    [
        'method' => 'POST',
        'uri' => '/widget/storageSettings',
        'action' => [WidgetController::class, 'saveStorageSettings'],
        'name' => 'widget.storageSettings',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/iconPack',
        'action' => [AppController::class, 'iconPack'],
        'name' => 'app.iconPack',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/getAll',
        'action' => [AppController::class, 'getAll'],
        'name' => 'app.getAll',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/get/{filter?}',
        'action' => [AppController::class, 'get'],
        'name' => 'app.get.filter',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/getConfig/{packageName}',
        'action' => [AppController::class, 'getConfig'],
        'name' => 'app.getConfig.packageName',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/setConfig/{packageName}/{key}',
        'action' => [AppController::class, 'setConfig'],
        'name' => 'app.setConfig.packageName.key',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/install',
        'action' => [AppController::class, 'install'],
        'name' => 'app.install',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/packageMeta/{filename}',
        'action' => [AppController::class, 'packageMeta'],
        'name' => 'app.packageMeta.filename',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/installPackage/{filename}',
        'action' => [AppController::class, 'installPackage'],
        'name' => 'app.installPackage.filename',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/installPackage/start',
        'action' => [AppController::class, 'installPackageStart'],
        'name' => 'app.installPackage.start',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/installPackage/status/{installId}',
        'action' => [AppController::class, 'installPackageStatus'],
        'name' => 'app.installPackage.status',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/database/checkPrefix',
        'action' => [AppController::class, 'checkDatabasePrefix'],
        'name' => 'app.database.checkPrefix',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/database/testConnection',
        'action' => [AppController::class, 'testDatabaseConnection'],
        'name' => 'app.database.testConnection',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/database/defaults',
        'action' => [AppController::class, 'databaseDefaults'],
        'name' => 'app.database.defaults',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/files',
        'action' => [AppController::class, 'files'],
        'name' => 'app.files',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/deleteFile',
        'action' => [AppController::class, 'deleteFile'],
        'name' => 'app.deleteFile',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/filesUpload',
        'action' => [AppController::class, 'filesUpload'],
        'name' => 'app.filesUpload',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/pinion/limits',
        'action' => [PinionController::class, 'limits'],
        'name' => 'app.pinion.limits',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/pinion/init',
        'action' => [PinionController::class, 'init'],
        'name' => 'app.pinion.init',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/pinion/upload',
        'action' => [PinionController::class, 'upload'],
        'name' => 'app.pinion.upload',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/pinion/complete',
        'action' => [PinionController::class, 'complete'],
        'name' => 'app.pinion.complete',
    ],
    [
        'method' => 'GET',
        'uri' => '/app/pinion/status/{uploadId}',
        'action' => [PinionController::class, 'status'],
        'name' => 'app.pinion.status',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/pinion/abort/{uploadId}',
        'action' => [PinionController::class, 'abort'],
        'name' => 'app.pinion.abort',
    ],
    [
        'method' => 'POST',
        'uri' => '/app/remove/{packageName}',
        'action' => [AppController::class, 'remove'],
        'name' => 'app.remove.packageName',
    ],
    [
        'method' => 'GET',
        'uri' => '/router/getAll',
        'action' => [RouterController::class, 'getAll'],
        'name' => 'router.getAll',
    ],
    [
        'method' => 'POST',
        'uri' => '/router/remove',
        'action' => [RouterController::class, 'remove'],
        'name' => 'router.remove',
    ],
    [
        'method' => 'POST',
        'uri' => '/router/save',
        'action' => [RouterController::class, 'save'],
        'name' => 'router.save',
    ],
    [
        'method' => 'GET',
        'uri' => '/account/getConnectData',
        'action' => [AccountController::class, 'getConnectData'],
        'name' => 'account.getConnectData',
    ],
    [
        'method' => 'GET',
        'uri' => '/account/connect',
        'action' => [AccountController::class, 'connect'],
        'name' => 'account.connect',
    ],
    [
        'method' => 'GET',
        'uri' => '/account/logout',
        'action' => [AccountController::class, 'logout'],
        'name' => 'account.logout',
    ],
    [
        'method' => 'GET',
        'uri' => '/market/getDownloads',
        'action' => [MarketController::class, 'getDownloads'],
        'name' => 'market.getDownloads',
    ],
    [
        'method' => 'POST',
        'uri' => '/market/deleteDownload',
        'action' => [MarketController::class, 'deleteDownload'],
        'name' => 'market.deleteDownload',
    ],
    [
        'method' => 'GET',
        'uri' => '/market/getApps/{keyword?}',
        'action' => [MarketController::class, 'getApps'],
        'name' => 'market.getApps.keyword',
    ],
    [
        'method' => 'GET',
        'uri' => '/market/getOneApp/{package_name}',
        'action' => [MarketController::class, 'getOneApp'],
        'name' => 'market.getOneApp.package_name',
    ],
    [
        'method' => 'POST',
        'uri' => '/market/downloadRequest/{package_name}',
        'action' => [MarketController::class, 'downloadRequest'],
        'name' => 'market.downloadRequest.package_name',
    ],
    [
        'method' => 'GET',
        'uri' => '/market/getTemplates/{package_name}',
        'action' => [MarketController::class, 'getTemplates'],
        'name' => 'market.getTemplates.package_name',
    ],
    [
        'method' => 'POST',
        'uri' => '/market/downloadRequestTemplate/{uid}',
        'action' => [MarketController::class, 'downloadRequestTemplate'],
        'name' => 'market.downloadRequestTemplate.uid',
    ],
    [
        'method' => 'GET',
        'uri' => '/notification',
        'action' => [NotificationController::class, 'index'],
        'name' => 'notification',
    ],
    [
        'method' => 'POST',
        'uri' => '/notification/hide',
        'action' => [NotificationController::class, 'hide'],
        'name' => 'notification.hide',
    ],
    [
        'method' => 'POST',
        'uri' => '/notification/seen',
        'action' => [NotificationController::class, 'seen'],
        'name' => 'notification.seen',
    ],
    [
        'method' => 'GET',
        'uri' => '/update/checkVersion/{type?}',
        'action' => [UpdateController::class, 'checkVersion'],
        'name' => 'update.checkVersion.type',
    ],
    [
        'method' => 'GET',
        'uri' => '/update/install',
        'action' => [UpdateController::class, 'install'],
        'name' => 'update.install',
    ],
    [
        'method' => 'GET',
        'uri' => '/template/get/{packageName}',
        'action' => [TemplateController::class, 'get'],
        'name' => 'template.get.packageName',
    ],
    [
        'method' => 'GET',
        'uri' => '/template/install/{uid}/{packageName}',
        'action' => [TemplateController::class, 'install'],
        'name' => 'template.install.uid.packageName',
    ],
    [
        'method' => 'GET',
        'uri' => '/template/installPackage/{filename}',
        'action' => [TemplateController::class, 'installPackage'],
        'name' => 'template.installPackage.filename',
    ],
    [
        'method' => 'GET',
        'uri' => '/template/set/{packageName}/{folderName}',
        'action' => [TemplateController::class, 'set'],
        'name' => 'template.set.packageName.folderName',
    ],
    [
        'method' => 'GET',
        'uri' => '/template/remove/{packageName}/{folderName}',
        'action' => [TemplateController::class, 'remove'],
        'name' => 'template.remove.packageName.folderName',
    ],
    ],
];

