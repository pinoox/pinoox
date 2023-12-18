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


namespace pinoox\component\Path\Manager;

abstract class Manager implements ManagerInterface
{
    private string $basePath;
    protected string $replaceSeparator = DIRECTORY_SEPARATOR;
    protected array $signs = ['/', '\\', '>'];
    protected bool $allowRepeat = false;

    public function __construct(string $basePath = '')
    {
        $this->setBasePath($basePath);
    }

    public function trim(string $path): string
    {
        $realPath = $path;

        if (!empty($this->signs)) {
            $realPath = $path = str_replace($this->signs, $this->replaceSeparator, $path);
        }

        if (!$this->allowRepeat) {
            $path = trim($path, $this->replaceSeparator);
            $path = implode($this->replaceSeparator, array_filter(explode($this->replaceSeparator, $path)));

            $path = str_ends_with($realPath, $this->replaceSeparator) ? $path . $this->replaceSeparator : $path;
            $path = str_starts_with($realPath, $this->replaceSeparator) ? $this->replaceSeparator . $path : $path;
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
        return $this->trim($path);
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