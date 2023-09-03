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


namespace pinoox\component\package\reference;


/**
 *
 * The ReferenceInterface defines the methods for retrieving package name and Path of a reference.
 */
interface ReferenceInterface
{
    /**
     * Returns the name of the package.
     * @return string|null The name of the package, or null if not available.
     */
    public function getPackageName(): ?string;

    /**
     * Returns the Path of the reference.
     * @return string|null The Path of the reference, or null if not available.
     */
    public function getPath(): ?string;

    /**
     * Returns the reference.
     * @return string|null The reference, or null if not available.
     */
    public function get(): ?string;
}
