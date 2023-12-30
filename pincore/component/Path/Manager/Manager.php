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


namespace Pinoox\Component\Path\Manager;

use Symfony\Component\Filesystem\Path;

abstract class Manager implements ManagerInterface
{
    private string $basePath;
    protected string $replaceSeparator = '/';
    protected string|array $signs = '\\';
    protected bool $isCanonicalize = true;

    public function __construct(string $basePath = '')
    {
        $this->setBasePath($basePath);
    }

    public function canonicalize(string $path): string
    {
        $realPath = $path;

        if (!empty($this->signs)) {
            $realPath = $path = str_replace($this->signs, $this->replaceSeparator, $path);
        }

        if ($this->isCanonicalize) {
            $path = Path::canonicalize($path);
            $path = str_ends_with($realPath, $this->replaceSeparator) ? $path . $this->replaceSeparator : $path;
        }

        return $path;
    }

    public function get(string $path = ''): string
    {
        $basePath = $this->getBasePath();
        if (empty($path)) {
            $path = $basePath;
        } else if (!empty($basePath)) {
            $path = $basePath . $this->replaceSeparator . $path;
        }
        return $this->canonicalize($path);
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }
}