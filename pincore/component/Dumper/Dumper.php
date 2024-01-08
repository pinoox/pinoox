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


namespace Pinoox\Component\Dumper;


class Dumper
{
    private string $basePath;

    public function __construct(
        string $basePath = ''
    )
    {
        $this->basePath = str_replace(['/', '\\'], '/', $basePath);
    }

    public function register()
    {
        $basePath = dirname($this->basePath);
        $format = $_SERVER['VAR_DUMPER_FORMAT'] ?? null;

        match (true) {
            'html' == $format => HtmlDumper::register($basePath),
            'cli' == $format => CliDumper::register($basePath),
            'server' == $format => null,
            $format && 'tcp' == parse_url($format, PHP_URL_SCHEME) => null,
            default => in_array(PHP_SAPI, ['cli', 'phpdbg']) ? CliDumper::register($basePath) : HtmlDumper::register($basePath),
        };
    }
}