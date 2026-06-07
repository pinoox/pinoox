<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal\App;

use Pinoox\Component\Package\Routing\DomainMatch;
use Pinoox\Component\Package\Routing\DomainMatcher;
use Pinoox\Component\Source\Portal;

/**
 * @method static mixed config(?string $key = NULL, mixed $default = NULL)
 * @method static string|null defaultHost()
 * @method static array hostMap()
 * @method static DomainMatch|null match(?string $host)
 * @method static bool isDefaultHost(?string $host)
 * @method static bool isCanonicalDefaultHost(?string $host)
 * @method static string normalizeHost(string $host)
 * @method static DomainMatcher ___()
 *
 * @see \Pinoox\Component\Package\Routing\DomainMatcher
 */
class Domain extends Portal
{
    public static function __register(): void
    {
        self::__bind(DomainMatcher::class);
    }

    public static function __name(): string
    {
        return 'domain';
    }

    /**
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function __callback(): array
    {
        return [];
    }
}

