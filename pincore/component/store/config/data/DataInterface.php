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


namespace pinoox\component\store\config\data;

interface DataInterface
{
    public function __construct(array $data = []);

    public function get(?string $key = null, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    public function add(string $key, mixed $value): void;

    public function merge(array $data): void;

    public function getData(): array;

    public function reset(): void;

    public function restore(): void;

    public function fromArray(array $arr): mixed;
}