<?php

namespace Pinoox\PinDoc;

use Pinoox\PinDoc\Api\ApiDocsGenerator;
use Pinoox\PinDoc\GraphQL\GraphQLDocsGenerator;

class PinDocHtmlBuilder
{
    public function __construct(
        private readonly PinDocMarkdownLoader $markdownLoader = new PinDocMarkdownLoader(),
        private readonly PinDocMarkdownConverter $markdownConverter = new PinDocMarkdownConverter(),
        private readonly PinDocHtmlRenderer $htmlRenderer = new PinDocHtmlRenderer(),
    ) {
    }

    public function fromRestApi(
        string $package,
        ?string $apiVersion = null,
        ?string $audience = null,
        ?string $markdownPath = null,
    ): string {
        $generator = new ApiDocsGenerator();
        $entries = (new \Pinoox\PinDoc\Api\AppApiRegistry())->all($package, $apiVersion);
        $docs = $this->resolveDocsFromEntries($entries, $package, 'rest');
        $extra = $this->markdownLoader->resolveForPackage($package, $docs, $markdownPath);

        return $generator->generate('html', $package, $apiVersion, $audience, $extra);
    }

    public function fromGraphQL(
        string $package,
        ?string $audience = null,
        ?string $markdownPath = null,
    ): string {
        $generator = new GraphQLDocsGenerator();
        $entries = (new \Pinoox\PinDoc\GraphQL\GraphQLRegistry())->all($package);
        $docs = $this->resolveDocsFromEntries($entries, $package, 'graphql');
        $extra = $this->markdownLoader->resolveForPackage($package, $docs, $markdownPath);

        return $generator->generate('html', $package, $audience, $extra);
    }

    public function fromMarkdownFile(
        string $path,
        string $title = 'Documentation',
        string $subtitle = '',
        ?string $package = null,
    ): string {
        $markdown = $package !== null
            ? $this->markdownLoader->read($package, $path)
            : file_get_contents($path);

        if (!is_string($markdown) || trim($markdown) === '') {
            throw new \RuntimeException('Markdown file is empty: ' . $path);
        }

        $bodyHtml = $this->markdownConverter->convert($markdown);
        $document = [
            'kind' => 'rest',
            'title' => $title,
            'developer' => $subtitle,
            'audience_label' => 'Markdown docs',
            'version' => 'v1',
            'app_version' => '',
            'package' => $package ?? '',
            'description' => '',
            'operations' => [],
            'tags' => [],
            'operation_count' => 0,
            'prose_html' => $bodyHtml,
        ];

        return $this->htmlRenderer->renderProse([$document]);
    }

    private function resolveDocsFromEntries(array $entries, string $package, string $kind): array
    {
        $entry = (new \Pinoox\PinDoc\Api\AppApiRegistry())->firstEntry($entries, $package);

        if ($entry === null) {
            return [];
        }

        $appMeta = is_array($entry['app_meta'] ?? null)
            ? $entry['app_meta']
            : AppDocProfile::fromPackage($package);

        return AppDocProfile::resolveDocs(
            is_array($entry['docs'] ?? null) ? $entry['docs'] : [],
            $appMeta,
            $kind,
        );
    }
}

