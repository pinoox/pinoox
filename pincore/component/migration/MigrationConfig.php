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

namespace pinoox\component\migration;

use pinoox\component\kernel\Exception;
use pinoox\portal\DB;

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
        $this->appPath = $path;
        $this->package = $package;
        $this->migrationPath = $this->appPath . $this->folders;

        //namespace
        if ($this->package == 'pincore') {
            $this->namespace = 'pinoox' . $this->folders;
        } else {
            $this->namespace = 'pinoox\\app\\' . $this->package . $this->folders;
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