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


namespace Pinoox\Component\Package\Reference;


class NameReference implements ReferenceInterface
{

    /**
     * @param string|null $packageName
     * @param string|null $path
     */
    public function __construct(private ?string $packageName, private ?string $path = null)
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
    public function getValue(): ?string
    {
        return $this->path;
    }

    /**
     * create path reference
     *
     * @param string|null $packageName
     * @param string|null $path
     * @return static
     */
    public static function create(?string $packageName, ?string $path = null): static
    {
        return new static($packageName, $path);
    }

    /**
     * {@inheritDoc}
     */
    public function get(): ?string
    {
        $package = $this->getPackageName();

        return !empty($package) ? $package . ':' . $this->getValue() : $this->getValue();
    }
}