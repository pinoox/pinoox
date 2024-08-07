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
     * @param string|null $value
     */
    public function __construct(private ?string $packageName, private ?string $value = null)
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
        return $this->value;
    }

    /**
     * create value reference
     *
     * @param string|null $packageName
     * @param string|null $value
     * @return static
     */
    public static function create(?string $packageName, ?string $value = null): static
    {
        return new static($packageName, $value);
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