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


namespace Pinoox\Component\Store\Config\Data;

interface DataInterface
{
    public function __construct($data = []);

    public function get(?string $key = null, mixed $default = null): mixed;

    public function all(): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    public function add(string $key, mixed $value): void;

    public function merge(array $data): void;

    public function getData();

    public function setData($data = []): void;

    public function reset(): void;

    public function restore(): void;

    public function fromArray(array $arr): mixed;
}