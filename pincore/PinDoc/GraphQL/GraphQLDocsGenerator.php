<?php

namespace Pinoox\PinDoc\GraphQL;

use Pinoox\PinDoc\AppDocProfile;
use Pinoox\PinDoc\DocsVisibility;
use Pinoox\PinDoc\PinDocHtmlRenderer;
use Pinoox\PinDoc\PinDocMarkdownRenderer;
use Pinoox\PinDoc\GraphQL\Docs\GraphQLDocBuilder;

class GraphQLDocsGenerator
{
    public function __construct(
        private readonly GraphQLRegistry $registry = new GraphQLRegistry(),
        private readonly GraphQLDocBuilder $builder = new GraphQLDocBuilder(),
        private readonly PinDocHtmlRenderer $htmlRenderer = new PinDocHtmlRenderer(),
        private readonly PinDocMarkdownRenderer $markdownRenderer = new PinDocMarkdownRenderer(),
    ) {
    }

    public function generate(
        string $format = 'md',
        ?string $app = null,
        ?string $audience = null,
        ?string $extraMarkdown = null,
    ): string {
        $entries = $this->registry->all($app);

        if ($entries === []) {
            throw new \RuntimeException('No GraphQL definitions found for the selected package.');
        }

        $documents = $this->builder->build($entries, $audience);

        return strtolower($format) === 'html'
            ? $this->htmlRenderer->render($documents, $extraMarkdown)
            : $this->markdownRenderer->render($documents);
    }

    public function defaultOutputRelativePath(string $package, string $format = 'md', ?string $audience = null): string
    {
        $entry = $this->registry->all($package)[$package] ?? null;
        $appMeta = is_array($entry['app_meta'] ?? null)
            ? $entry['app_meta']
            : AppDocProfile::fromPackage($package);
        $docs = AppDocProfile::resolveDocs(
            is_array($entry['docs'] ?? null) ? $entry['docs'] : [],
            $appMeta,
            'graphql',
        );

        return DocsVisibility::outputPath($docs, $format, $audience, 'graphql');
    }
}

