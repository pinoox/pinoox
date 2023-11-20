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


namespace pinoox\component\template\parser;


use pinoox\component\helpers\HelperString;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReference;
use Symfony\Component\Templating\TemplateReferenceInterface;

class TemplateNameParser implements TemplateNameParserInterface
{
    const TWIG = 'twig';
    const PHP = 'php';
    const TWIG_PHP = 'twig.php';

    const ENGINES = [
        self::TWIG_PHP,
        self::TWIG,
        self::PHP,
    ];

    public function parse(TemplateReferenceInterface|string $name): TemplateReferenceInterface
    {
        if ($name instanceof TemplateReferenceInterface) {
            return $name;
        }

        if (HelperString::lastHas($name, '.' . self::TWIG_PHP)) {
            $engine = self::TWIG_PHP;
        } else if (false !== $pos = strrpos($name, '.')) {
            $engine = substr($name, $pos + 1);
        } else {
            $engine = 'twig';
        }

        return new TemplateReference($name, $engine);
    }
}