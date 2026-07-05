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

namespace App\com_pinoox_manager\Controller;

use App\com_pinoox_manager\Component\AppHelper;
use App\com_pinoox_manager\Component\AppIconPack;
use App\com_pinoox_manager\Component\InstallSession;
use App\com_pinoox_manager\Component\PackageDatabase;
use App\com_pinoox_manager\Component\PackagePaths;
use App\com_pinoox_manager\Component\Wizard;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\JsonResponse;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\App\AppEngine;

class AppController extends ApiController
{
    public function get($filter = null)
    {
        switch ($filter) {
            case 'installed':
            {
                $result = AppHelper::getAll(false);
                break;
            }
            case 'systems':
            {
                $result = AppHelper::getAll(true);
                break;
            }
            default:
            {
                $result = AppHelper::getAll(null, true);
            }
        }

        return $result;
    }

    public function getConfig($packageName)
    {
        return AppHelper::getOne($packageName);
    }

    public function setConfig(Request $request, $packageName, $key)
    {
        $config = $request->payload('config');

        if ($key == 'dock')
            $config = !$config;
        if ($key == 'router') {
            $routerConfig = AppEngine::config($packageName)->get('router');

            if (!is_array($routerConfig)) {
                $routerConfig = ['routes' => []];
            }

            $routerConfig['type'] = $config === 'multiple' ? 'single' : 'multiple';
            $config = $routerConfig;
        }

        if (!is_null($config)) {
            AppEngine::config($packageName)
                ->set($key, $config)
                ->save();

            return $this->message('manager.config_saved_successfully');
        }

        return $this->deny('manager.invalid_request');
    }

    public function install(Request $request)
    {
        $this->validated($request, [
            'file' => [
                'file',
                function ($attribute, $value, $fail) {
                    if (strtolower($value->getClientOriginalExtension()) !== 'pinx') {
                        $fail('آپلود فایل با پسوند .pinx مجاز است!');
                    }
                }
            ],
        ]);

        PackagePaths::ensureManualDir();

        $upload = $request->files->get('file');
        $filename = $upload->getClientOriginalName();
        $upload->move(PackagePaths::manualDir(), $filename);

        $pinxFile = PackagePaths::manualFile($filename);

        if (Wizard::installFromManual($pinxFile)['success']) {
            return $this->message('manager.installed_successfully');
        }

        $message = Wizard::getMessage();

        if (empty($message)) {
            return $this->error('manager.error_happened');
        }

        return $this->deny($message);
    }

    public function getAll(Request $request)
    {
        return AppHelper::getAll();
    }

    public function iconPack()
    {
        return [
            'provider' => AppIconPack::info(),
            'defaults' => AppIconPack::systemDefaults(),
            'usage' => AppIconPack::usage(),
        ];
    }

    public function installPackage($filename)
    {
        if (empty($filename))
            return $this->deny('manager.request_install_app_not_valid');

        $filename = basename($filename);
        $pinxFile = PackagePaths::manualFile($filename);
        if (!is_file($pinxFile))
            return $this->deny('manager.request_install_app_not_valid');

        $result = Wizard::installFromManual($pinxFile);

        if (!empty($result['success'])) {
            return $this->message('manager.installed_successfully', $result);
        }

        $message = Wizard::getMessage() ?: ($result['message'] ?? null);

        if (empty($message))
            return $this->deny('manager.request_install_app_not_valid');

        return $this->deny($message);
    }

    public function installPackageStart(Request $request)
    {
        $filename = basename((string) $request->payload('filename', ''));

        if ($filename === '') {
            return $this->deny('manager.request_install_app_not_valid');
        }

        $pinxFile = PackagePaths::manualFile($filename);

        if (!is_file($pinxFile)) {
            return $this->deny('manager.request_install_app_not_valid');
        }

        $sessionId = InstallSession::create($filename);
        $options = $this->installOptionsFromRequest($request);
        $options['session_id'] = $sessionId;

        if (function_exists('fastcgi_finish_request')) {
            $this->sendEarlyJson($this->ok([
                'install_id' => $sessionId,
                'polling' => true,
            ]));
            @session_write_close();

            try {
                $result = Wizard::installFromManual($pinxFile, $options);
                InstallSession::complete($sessionId, $result);
            } catch (\Throwable $e) {
                InstallSession::complete($sessionId, [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'steps' => [],
                ]);
            }

            exit;
        }

        $result = Wizard::installFromManual($pinxFile, $options);
        InstallSession::complete($sessionId, $result);

        if (!empty($result['success'])) {
            return $this->message('manager.installed_successfully', $result);
        }

        $message = Wizard::getMessage() ?: ($result['message'] ?? null);

        if (empty($message)) {
            return $this->deny('manager.request_install_app_not_valid');
        }

        return $this->deny($message);
    }

    public function installPackageStatus($installId)
    {
        $session = InstallSession::get((string) $installId);

        if ($session === null) {
            return $this->deny('manager.error_happened');
        }

        return $session;
    }

    public function checkDatabasePrefix(Request $request)
    {
        $prefix = PackageDatabase::formatPrefix((string) $request->payload('prefix', ''));
        $package = (string) $request->payload('package_name', '');
        $resolved = PackageDatabase::resolvePrefix($package, $prefix);

        return [
            'prefix' => $prefix,
            'resolved_prefix' => $resolved,
            'auto_adjusted' => $resolved !== $prefix,
            'tables_exist' => PackageDatabase::prefixTablesExist($prefix),
            'tables_exist_resolved' => PackageDatabase::prefixTablesExist($resolved),
        ];
    }

    public function testDatabaseConnection(Request $request)
    {
        $input = $request->payloadMany('connection,host,database,username,password,prefix,port', '', false);
        $connected = PackageDatabase::testConnection($input);

        if ($connected) {
            return $this->message('manager.database_connection_ok');
        }

        return $this->deny('manager.database_connection_failed');
    }

    public function databaseDefaults()
    {
        return PackageDatabase::platformDefaults();
    }

    /**
     * @return array<string, mixed>
     */
    private function installOptionsFromRequest(Request $request): array
    {
        $database = $request->payload('database');

        if (!is_array($database) || $database === []) {
            return [];
        }

        return ['database' => $database];
    }

    private function sendEarlyJson(JsonResponse $response): void
    {
        $response->send();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    public function packageMeta($filename)
    {
        if (empty($filename))
            return $this->deny('manager.error_happened');

        $filename = basename($filename);
        $pinxFile = PackagePaths::manualFile($filename);

        if (!is_file($pinxFile))
            return $this->deny('manager.error_happened');

        try {
            return Wizard::pullPackageMeta($pinxFile);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function files()
    {
        $files = PackagePaths::listManualFiles();
        $files = array_map(function ($file) {
            try {
                $data = Wizard::pullPackageMeta($file);
            } catch (\Throwable) {
                Wizard::deletePackageFile($file);
                return false;
            }

            if ($data['type'] === 'app' && !Wizard::isValidNamePackage($data['package_name'])) {
                Wizard::deletePackageFile($file);
                return false;
            }

            if ($data['type'] === 'theme' && !Wizard::isValidNamePackage($data['app'])) {
                Wizard::deletePackageFile($file);
                return false;
            }

            return $data;
        }, $files);

        return array_values(array_filter($files));
    }

    public function deleteFile(Request $request)
    {
        $filename = $request->payload('filename');

        if (empty($filename))
            return $this->deny('manager.error_happened');

        $pinxFile = PackagePaths::manualFile($filename);
        if (!is_file($pinxFile))
            return $this->deny('manager.error_happened');

        Wizard::deletePackageFile($pinxFile);

        return $this->message('manager.delete_successfully');
    }

    public function filesUpload(Request $request)
    {
        if (!$request->files->has('files'))
            return $this->deny('manager.invalid_request');

        $path = PackagePaths::ensureManualDir();

        $files = $request->files->all('files');
        if (!is_array($files))
            $files = [$files];

        $uploaded = 0;
        foreach ($files as $file) {
            if (strtolower($file->getClientOriginalExtension()) !== 'pinx')
                continue;
            $file->move($path, $file->getClientOriginalName());
            $uploaded++;
        }

        if ($uploaded === 0)
            return $this->deny('manager.error_happened');

        return $this->message(
            $uploaded === 1 ? 'manager.file_uploaded_correctly' : 'manager.files_uploaded_correctly'
        );
    }

    public function remove($packageName)
    {
        if (empty($packageName))
            return $this->deny('manager.request_not_valid');

        if (!AppEngine::exists($packageName))
            return $this->deny('manager.request_not_valid');

        $config = AppEngine::config($packageName);

        if ($config->get('sys-app')) {
            return $this->deny('manager.cannot_delete_system_app');
        }

        if (!Wizard::deleteApp($packageName)) {
            $message = Wizard::getMessage();

            if (empty($message)) {
                return $this->deny('manager.error_happened');
            }

            return $this->deny($message);
        }

        return $this->message('manager.delete_successfully');
    }
}
