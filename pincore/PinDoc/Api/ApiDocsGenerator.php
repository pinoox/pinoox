<?php

namespace Pinoox\PinDoc\Api;

use Pinoox\PinDoc\Api\Docs\ApiDocBuilder;
use Pinoox\PinDoc\AppDocProfile;
use Pinoox\PinDoc\DocsVisibility;
use Pinoox\PinDoc\PinDocHtmlRenderer;
use Pinoox\PinDoc\PinDocMarkdownRenderer;

class ApiDocsGenerator
{
    public function __construct(
        private readonly AppApiRegistry $registry = new AppApiRegistry(),
        private readonly ApiDocBuilder $builder = new ApiDocBuilder(),
        private readonly PinDocHtmlRenderer $htmlRenderer = new PinDocHtmlRenderer(),
        private readonly PinDocMarkdownRenderer $markdownRenderer = new PinDocMarkdownRenderer(),
    ) {
    }

    public function generate(
        string $format = 'md',
        ?string $app = null,
        ?string $version = null,
        ?string $audience = null,
        ?string $extraMarkdown = null,
    ): string {
        $entries = $this->registry->all($app, $version);

        if ($entries === []) {
            throw new \RuntimeException('No API routes found for the selected package.');
        }

        $documents = $this->builder->build($entries, $audience);
        usort($documents, fn(array $a, array $b) => strcmp((string)($a['version'] ?? ''), (string)($b['version'] ?? '')));

        return strtolower($format) === 'html'
            ? $this->htmlRenderer->render($documents, $extraMarkdown)
            : $this->markdownRenderer->render($documents);
    }

    public function defaultOutputRelativePath(string $package, string $format = 'md', ?string $audience = null): string
    {
        $entry = $this->registry->firstEntry($this->registry->all($package), $package);
        $appMeta = is_array($entry['app_meta'] ?? null)
            ? $entry['app_meta']
            : AppDocProfile::fromPackage($package);
        $docs = AppDocProfile::resolveDocs(
            is_array($entry['docs'] ?? null) ? $entry['docs'] : [],
            $appMeta,
            'rest',
        );

        return DocsVisibility::outputPath($docs, $format, $audience);
    }
}

