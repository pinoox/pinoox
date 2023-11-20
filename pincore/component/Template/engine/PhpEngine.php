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


namespace pinoox\component\template\engine;

use Symfony\Component\Templating\Helper\SlotsHelper;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Symfony\Component\Templating\PhpEngine as PhpEngineSymfony;

class PhpEngine implements EngineInterface
{
    private FilesystemLoader $loader;
    private TemplateNameParserInterface $parser;
    private PhpEngineSymfony $template;

    /**
     * PhpEngine constructor.
     *
     * @param TemplateNameParserInterface $parser
     * @param string|array $folder
     * @param string|null $rootPath
     */
    public function __construct(TemplateNameParserInterface $parser, string|array $folder, ?string $rootPath = null)
    {
        $paths = $this->buildPaths($rootPath, $folder);
        $this->loader = new FilesystemLoader($paths);
        $this->parser = $parser;
        $this->template = new PhpEngineSymfony($this->parser, $this->loader, [new SlotsHelper()]);
    }

    /**
     * build paths template
     *
     * @param string|null $rootPath
     * @param string|array $folders
     * @return array|string
     */
    private function buildPaths(?string $rootPath, string|array $folders): array|string
    {
        $paths = [];
        if (is_array($folders)) {
            foreach ($folders as $folder) {
                $paths[] = $this->buildPaths($rootPath, $folder);
            }
        } else {
            $paths = $rootPath . '/' . $folders . '/' . '%name%';
        }

        return $paths;
    }

    /**
     * {@inheritDoc}
     */
    public function render(TemplateReferenceInterface|string $name, array $parameters = []): string
    {
        return $this->template->render($name, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(TemplateReferenceInterface|string $name): bool
    {
        return $this->template->exists($name);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(TemplateReferenceInterface|string $name): bool
    {
        $reference = $this->parser->parse($name);
        return 'php' === $reference->get('engine');
    }
}