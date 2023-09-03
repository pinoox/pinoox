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

namespace pinoox\component\template;

use RuntimeException;

interface ViewInterface
{
    /**
     * Renders a template.
     *
     * @param string $name
     * @param array $parameters
     * @return string
     * @throws RuntimeException if the template cannot be rendered
     */
    public function renderFile(string $name, array $parameters = []): string;

    /**
     * Returns true if the template exists.
     *
     * @param string $name
     * @return bool
     * @throws RuntimeException if the engine cannot handle the template name
     */
    public function existsFile(string $name): bool;

    /**
     * Renders a template.
     *
     * @param string $name
     * @param array $parameters
     * @return string
     * @throws RuntimeException if the template cannot be rendered
     */
    public function render(string $name, array $parameters = []): string;

    /**
     * Returns true if the template exists.
     *
     * @param string $name
     * @return bool
     * @throws RuntimeException if the engine cannot handle the template name
     */
    public function exists(string $name): bool;

    /**
     * Returns the assigned globals data.
     */
    public function getAll(): array;

    /**
     * Returns the assigned one global data.
     *
     * @param string|int $index
     * @return mixed
     */
    public function get(string|int $index): mixed;


    /**
     * Set global data
     *
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name, mixed $value): void;

    /**
     * get content ready
     *
     * @return string
     */
    public function getContentReady(): string;

    /**
     * add ready render
     *
     * @param string $name
     * @param array $parameters
     * @return ViewInterface
     */
    public function ready(string $name = '', array $parameters = []): ViewInterface;
}