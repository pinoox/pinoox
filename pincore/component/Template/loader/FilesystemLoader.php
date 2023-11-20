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


namespace pinoox\component\template\loader;


use Symfony\Component\Templating\Storage\Storage;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface as LoaderInterfaceTwig;
use Symfony\Component\Templating\Loader\LoaderInterface as LoaderInterfaceSymfony;
use Twig\Source;

class FilesystemLoader implements LoaderInterfaceTwig,LoaderInterfaceSymfony
{

    public function getCacheKey(string $name): string
    {
        // TODO: Implement getCacheKey() method.
    }

    public function load(TemplateReferenceInterface $template): Storage|false
    {
        // TODO: Implement load() method.
    }

    public function getSourceContext(string $name): Source
    {
        // TODO: Implement getSourceContext() method.
    }

    public function isFresh(string $name, int $time): bool
    {
        // TODO: Implement isFresh() method.
    }

    public function exists(string $name)
    {
        // TODO: Implement exists() method.
    }
}