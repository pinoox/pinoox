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


namespace Pinoox\Component\Template\Reference;


use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Path\Manager\PathManager;

class TemplatePathReference
{
    public function __construct(
        private string|array $folders,
        private string       $themePath
    )
    {
    }

    public function current(): string
    {
        $folder = is_string($this->folders) ? $this->folders : @$this->folders[0];
        return Str::ds($this->themePath . '/' . $folder);
    }

    public function all(): array
    {
        $paths = [];
        if (is_array($this->folders)) {
            foreach ($this->folders as $folder) {
                $paths[] = Str::ds($this->themePath . '/' . $folder);
            }
        } else {
            $paths[] = Str::ds($this->themePath . '/' . $this->folders);
        }

        return $paths;
    }

    public function assets(string $file = ''): string
    {
        if ($f = $this->file($file)) {
            return $f;
        }

        $pathManager = new PathManager($this->current());
        return $pathManager->get($file);
    }

    public function file(string $file): bool|string
    {
        $paths = $this->all();
        foreach ($paths as $path) {
            $file = Str::ds($path . '/' . $file);
            if (is_file($file))
                return $file;
        }

        return false;
    }

    public function exists(string $file): bool
    {
        return !!$this->file($file);
    }
}