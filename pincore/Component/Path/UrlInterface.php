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

namespace Pinoox\Component\Path;

interface UrlInterface
{
    public function origin(bool $absolute = true): string;

    public function link(string $link = '', string $scope = Url::SCOPE_APP, string $mode = Url::MODE_AUTO): string;

    public function to(string $path = '', string $scope = Url::SCOPE_APP): string;

    public function asset(string $path = '', ?string $package = null): string;
}

