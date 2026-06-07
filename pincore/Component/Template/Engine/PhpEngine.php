<?php

namespace Pinoox\Component\Template\Engine;

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
     * @param string|list<string> $paths Absolute theme directories (child first)
     */
    public function __construct(TemplateNameParserInterface $parser, string|array $paths)
    {
        $this->loader = new FilesystemLoader($this->buildLoaderPaths($paths));
        $this->parser = $parser;
        $this->template = new PhpEngineSymfony($this->parser, $this->loader, [new SlotsHelper()]);
    }

    /**
     * @param string|list<string> $paths
     * @return list<string>
     */
    private function buildLoaderPaths(string|array $paths): array
    {
        $paths = is_array($paths) ? $paths : [$paths];
        $loaderPaths = [];

        foreach ($paths as $path) {
            $path = rtrim(str_replace('\\', '/', (string) $path), '/');
            if ($path === '') {
                continue;
            }

            $loaderPaths[] = $path . '/%name%';
        }

        return $loaderPaths;
    }

    public function render(TemplateReferenceInterface|string $name, array $parameters = []): string
    {
        return $this->template->render($name, $parameters);
    }

    public function exists(TemplateReferenceInterface|string $name): bool
    {
        return $this->template->exists($name);
    }

    public function supports(TemplateReferenceInterface|string $name): bool
    {
        $reference = $this->parser->parse($name);

        return 'php' === $reference->get('engine');
    }
}

