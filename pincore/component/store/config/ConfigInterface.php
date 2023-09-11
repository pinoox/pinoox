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


namespace pinoox\component\store\config;


interface ConfigInterface
{
    /**
     *  Get data from config
     *
     * @param string|null $key
     * @return mixed
     */
    public function get(?string $key = null): mixed;

    /**
     * Set data in config
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set(string $key, mixed $value): static;


    /**
     * Set data in config
     *
     * @param string $key
     * @return $this
     */
    public function remove(string $key): static;

    /**
     * Save data on config file
     *
     * @return $this
     */
    public function save(): static;
}