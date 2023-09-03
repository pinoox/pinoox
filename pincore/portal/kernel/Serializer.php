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

namespace pinoox\portal\kernel;

use pinoox\component\source\Portal;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class Serializer extends Portal
{

    public static function __register(): void
    {
        self::__bind(JsonEncoder::class, 'encoder.json');
        self::__bind(XmlEncoder::class, 'encoder.xml');
        self::__bind(CsvEncoder::class, 'encoder.csv');

        self::__bind(ObjectNormalizer::class, 'normalizer');

        self::__bind(\Symfony\Component\Serializer\Serializer::class)
            ->setArguments([
                [
                    self::__ref('normalizer')
                ],
                [
                    self::__ref('encoder.json'),
                    self::__ref('encoder.xml'),
                    self::__ref('encoder.csv')
                ]
            ]);
    }

    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'kernel.serializer';
    }


    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [];
    }


    /**
     * Get exclude method names .
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }
}
