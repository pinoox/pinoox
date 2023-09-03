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


namespace pinoox\component\store\config\strategy;

interface ConfigStrategyInterface
{
    /**
     * Loads the configuration from a data source.
     */
    public function load(): void;

    /**
     * Saves the configuration to a data source.
     *
     */
    public function save(): void;

    /**
     * Sets the value of a configuration key.
     *
     * @param string $key
     * @param mixed $value
     *
     */
    public function set(string $key, mixed $value): void;

    /**
     * Adds a new configuration key and value.
     *
     * @param string $key
     * @param mixed $value
     *
     */
    public function add(string $key, mixed $value): void;

    /**
     * Gets the value of a configuration key.
     *
     * @param string|null $key
     *
     * @return array
     */
    public function get(string $key = null): mixed;

    /**
     * Removes a configuration key.
     *
     * @param string $key
     *
     */
    public function remove(string $key): void;

    /**
     * Resets the configuration to its default state.
     *
     */
    public function reset(): void;

    /**
     * Merges the provided array with the existing data.
     *
     * @param array $array The array to merge with the existing data.
     */
    public function merge(array $array): void;

}
