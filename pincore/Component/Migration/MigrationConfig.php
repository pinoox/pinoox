<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       pinoox.com
 * @copyright  pinoox
 */

namespace Pinoox\Component\Migration;

use Pinoox\Component\Kernel\Exception;
use Pinoox\Portal\Database\DB;
use Pinoox\Support\SystemConfig;
use Pinoox\Support\SystemApp;

class MigrationConfig
{
    private array|null $errors = null;
    public string|null $appPath = null;
    public string|null $migrationPath = null;
    public string|null $namespace = null;
    public string|null $package = null;
    private array|null $config;
    public string|null $folders =  '\\database\\migrations\\';


    public function load(string $path, string $package): MigrationConfig
    {
        $this->appPath = $package === 'pincore' ? SystemApp::basePath() : $path;
        $this->package = $package;
        $this->folders = '\\' . trim(SystemConfig::rawPath('app_migrations', 'database/migrations'), '\\/') . '\\';
        $this->migrationPath = $package === 'pincore'
            ? SystemConfig::path('system_migrations')
            : $this->appPath . $this->folders;

        //namespace
        if ($this->package == 'pincore') {
            $this->namespace = 'pinoox' . $this->folders;
        } else {
            $this->namespace = 'App\\' . $this->package . $this->folders;
        }

        //check database
        if ($this->isPrepareDB()) {
            try {
                $this->config = DB::getConfig();
            } catch (Exception $e) {
                $this->setError($e);
            }
        }

        return $this;
    }

    public function isPrepareDB(): bool
    {
        if (empty(DB::getConnection())) {
            $this->setError('Database not connected');
            return false;
        }
        return true;
    }

    public function getLastError()
    {
        return !empty($this->errors) ? end($this->errors) : null;
    }

    private function setError($err): void
    {
        $this->errors[] = $err;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }
}
