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

namespace Pinoox\Component\Database\Seeder;

use Pinoox\Portal\App\AppEngine;
use Symfony\Component\Finder\Finder;

class SeederToolkit
{
    private string $package = '';
    private string $seederPath = '';
    private string $seederFolder = 'Database/Seeders';
    private array $errors = [];
    private array $seeders = [];

    public function package(string $package): self
    {
        $this->package = $package;
        return $this;
    }

    public function load(): self
    {
        try {
            $this->initializeSeederPath();
            $this->loadSeederFiles();
        } catch (\Exception $e) {
            $this->addError($e);
        }

        return $this;
    }

    public function getSeeders(): array
    {
        return $this->seeders;
    }

    public function getErrors(bool $latest = true): array|string
    {
        if ($latest) {
            return end($this->errors) ?: '';
        }
        return $this->errors;
    }

    public function isSuccess(): bool
    {
        return empty($this->errors);
    }

    private function initializeSeederPath(): void
    {
        if ($this->package === 'pincore') {
            $this->seederPath = path('~pincore') . '/' . $this->seederFolder;
        } else {
            $this->seederPath = AppEngine::path($this->package) . '/' . $this->seederFolder;
        }
    }

    private function loadSeederFiles(): void
    {
        $this->ensureSeederDirectoryExists();

        $finder = new Finder();

        if (!is_dir($this->seederPath)) {
            return;
        }

        $finder->in($this->seederPath)->files()->name('*.php');

        foreach ($finder as $file) {
            $seederClass = require $file->getRealPath();
            if ($seederClass instanceof SeederBase) {
                $this->seeders[] = [
                    'file' => $file->getRealPath(),
                    'class' => get_class($seederClass),
                    'instance' => $seederClass,
                ];
            }
        }
    }

    private function ensureSeederDirectoryExists(): void
    {
        if (!is_dir($this->seederPath)) {
            mkdir($this->seederPath, 0755, true);
        }
    }

    private function addError(\Exception|\Throwable|string $error): void
    {
        if (is_string($error)) {
            $this->errors[] = $error;
        } else {
            $this->errors[] = $error->getMessage();
        }
    }
} 