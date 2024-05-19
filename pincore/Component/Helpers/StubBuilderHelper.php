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

namespace Pinoox\Component\Helpers;

use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\StubGenerator;
use Symfony\Component\Finder\Finder;

class StubBuilderHelper
{
    private string $package;
    private string $name;
    public $message;
    private string $prefix;
    private string $file;
    private string $classname;
    private string $sub;
    private string $namespace;
    private array $options = [];

    public function __construct(string $name, string $package, string $prefix = '', string $namespace = '')
    {
        $this->name = $name;
        $this->package = $package;
        $this->prefix = Str::toCamelCase($prefix);
        $this->buildSubAndClassname();
        $this->namespace = $namespace . $this->sub;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    private function buildSubAndClassname(): void
    {
        $parts = explode('\\', $this->name);
        $parts = array_map(fn($str) => Str::toCamelCase($str), $parts);
        $this->classname = array_pop($parts);
        $namespace = implode('\\', $parts);
        $this->sub = !empty($namespace) ? '\\' . $namespace : '';
    }


    public function get(): array
    {
        return [
            'package' => $this->package,
            'classname' => $this->classname . $this->prefix,
            'sub' => $this->sub,
            'path' => $this->getExportPath(),
            'name' => $this->name,
            'namespace' => $this->namespace,
            'prefix' => $this->prefix,
        ];
    }


    public function generate($stubFilename, array $options = []): bool
    {
        $options = [
            ...$this->options,
            ...$options,
            'copyright' => StubGenerator::get('copyright.stub'),
            'package' => $this->package,
            'classname' => $this->classname . $this->prefix,
            'sub' => $this->sub,
        ];
        try {
            $isCreated = StubGenerator::generate($stubFilename, $this->getExportPath(), $options);

            if ($isCreated) {
                $this->message = '✓ ' . $this->prefix . ' [' . $this->name . '] created successfully';
                return true;
            } else {
                $this->message = 'Can\'t generate a new ' . $this->prefix . '!';
                return false;
            }
        } catch (\Exception $e) {
            $this->message = $e;
            return false;
        }
    }


    private function getExportPath(): string
    {
        if (!empty($this->file))
            return $this->file;

        $mainFolder = !empty($this->prefix) ? '/' . $this->prefix : $this->prefix;
        $sub = str_replace('\\', '/', $this->sub);
        $path = AppEngine::path($this->package) . $mainFolder . $sub;

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        } else {
            //check availability
            $finder = new Finder();
            $finder->in($path)
                ->files()
                ->name('*.php');
        }

        return $path . '/' . $this->classname . $this->prefix . '.php';
    }
}