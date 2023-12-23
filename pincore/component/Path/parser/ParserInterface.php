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


namespace pinoox\component\Path\parser;

use pinoox\component\Path\reference\ReferenceInterface;

interface ParserInterface
{
    /**
     * Convert a template name to a TemplateReferenceInterface instance.
     * @param string|ReferenceInterface $name
     * @return ReferenceInterface
     */
    public function parse(string|ReferenceInterface $name): ReferenceInterface;
}