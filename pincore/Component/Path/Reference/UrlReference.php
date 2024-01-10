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


namespace Pinoox\Component\Path\Reference;

class UrlReference implements ReferenceInterface
{

    /**
     * @param string|null $packageName
     * @param string|null $url
     */
    public function __construct(private ?string $packageName, private ?string $url = null)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getPackageName(): ?string
    {
        return $this->packageName;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): ?string
    {
        return $this->url;
    }

    /**
     * create URL reference
     *
     * @param string|null $packageName
     * @param string|null $url
     * @return static
     */
    public static function create(?string $packageName, ?string $url = null): static
    {
        return new static($packageName, $url);
    }

    /**
     * {@inheritDoc}
     */
    public function get(): ?string
    {
        $package = $this->getPackageName();

        return !empty($package) ? $package . ':' . $this->getPath() : $this->getPath();
    }
}