<?php

namespace Pinoox\PinDoc;

class PinDocHtmlRenderer
{
    private function visible(array $document, string $flag): bool
    {
        return DocsVisibility::isVisible($document, $flag);
    }

    public function render(array $documents, ?string $extraMarkdown = null): string
    {
        $cssPath = __DIR__ . '/resources/pin-doc.css';
        $css = is_file($cssPath) ? file_get_contents($cssPath) : '';

        $versioned = $this->hasMultipleApiVersions($documents);
        $defaultSlug = $this->defaultVersionSlug($documents);
        $package = (string)($documents[0]['package'] ?? '');

        $body = '';
        $nav = '';
        $titles = [];
        $extraInjected = false;

        foreach ($documents as $document) {
            $titles[] = (string)$document['title'];
            $slug = $this->versionSlug((string)$document['version']);
            $hidden = $versioned && $slug !== $defaultSlug ? ' is-hidden' : '';

            $nav .= '<div class="sidebar-version' . $hidden . '" data-doc-version="' . $this->e($slug) . '">';
            if ($versioned) {
                $nav .= '<a class="nav-item nav-overview" href="#overview-' . $this->e($slug) . '" data-nav-target="overview-' . $this->e($slug) . '">Overview</a>';
            }
            $nav .= $this->renderNav($document) . '</div>';

            $injectExtra = !$extraInjected ? $extraMarkdown : null;
            $extraInjected = true;

            $body .= '<div class="doc-version' . $hidden . '" data-doc-version="' . $this->e($slug) . '">'
                . $this->renderDocument($document, $versioned, $injectExtra)
                . '</div>';
        }

        $body .= $this->renderFooter();

        $title = count($titles) === 1 ? $titles[0] : 'API Documentation';

        return $this->renderShell($title, $package, $defaultSlug, $versioned, $css, $this->renderTopbar($documents, $versioned), $this->renderSidebar($nav, $versioned), $this->renderContentToolbar() . $body);
    }

    public function renderProse(array $documents): string
    {
        $cssPath = __DIR__ . '/resources/pin-doc.css';
        $css = is_file($cssPath) ? file_get_contents($cssPath) : '';
        $first = $documents[0] ?? [];
        $title = (string)($first['title'] ?? 'Documentation');
        $package = (string)($first['package'] ?? '');

        $body = '<article class="info-card md-content prose-page">'
            . (string)($first['prose_html'] ?? '')
            . '</article>'
            . $this->renderFooter();

        return $this->renderShell($title, $package, 'v1', false, $css, $this->renderTopbar($documents, false), '', $body);
    }

    private function renderShell(
        string $title,
        string $package,
        string $defaultSlug,
        bool $versioned,
        string $css,
        string $topbar,
        string $sidebar,
        string $content,
    ): string {
        return '<!doctype html><html lang="en" data-theme="dark"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $this->e($title) . '</title>'
            . '<script>(function(){try{var t=localStorage.getItem("pindoc-theme");document.documentElement.setAttribute("data-theme",t==="light"?"light":"dark");}catch(e){document.documentElement.setAttribute("data-theme","dark");}})();</script>'
            . '<style>' . $css . '</style></head><body'
            . ($package !== '' ? ' data-doc-package="' . $this->e($package) . '"' : '')
            . ' data-default-version="' . $this->e($defaultSlug) . '"'
            . ($versioned ? ' data-multi-version="1"' : '')
            . '>'
            . $topbar
            . '<div class="layout' . ($sidebar === '' ? ' layout-prose' : '') . '">'
            . $sidebar
            . '<main class="content">' . $content . '</main>'
            . '</div>'
            . '<button type="button" class="back-to-top" id="back-to-top" aria-label="Back to top">↑</button>'
            . $this->script()
            . '</body></html>';
    }

    private function renderFooter(): string
    {
        return '<footer class="docs-footer">'
            . '<a class="docs-watermark" href="https://pinoox.com" target="_blank" rel="noopener noreferrer">'
            . 'Powered by <strong>Pinoox</strong> · pinoox.com'
            . '</a>'
            . '</footer>';
    }

    private function renderTopbar(array $documents, bool $versioned = false): string
    {
        $first = $documents[0] ?? [];
        $kind = strtoupper((string)($first['kind'] ?? 'rest'));
        $appVersion = trim((string)($first['app_version'] ?? ''));

        $versionMeta = $versioned
            ? $this->renderVersionSelector($documents)
            : '<span class="badge">API ' . $this->e((string)($first['version'] ?? '1')) . '</span>';

        return '<header class="topbar">'
            . '<div class="topbar-brand">'
            . '<button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle menu">Menu</button>'
            . '<div><h1>' . $this->e((string)($first['title'] ?? 'API Documentation')) . '</h1>'
            . '<p class="topbar-subtitle">' . $this->e((string)($first['developer'] ?? '')) . '</p></div>'
            . '</div>'
            . '<div class="topbar-meta">'
            . '<span class="badge badge-audience">' . $this->e((string)($first['audience_label'] ?? 'Public API docs')) . '</span>'
            . '<span class="badge">' . $this->e($kind) . '</span>'
            . $versionMeta
            . ($appVersion !== '' ? '<span class="badge">App ' . $this->e($appVersion) . '</span>' : '')
            . ($this->visible($first, 'package') ? '<span class="badge">' . $this->e((string)($first['package'] ?? '')) . '</span>' : '')
            . '<button type="button" class="theme-toggle" id="theme-toggle" aria-label="Toggle color theme" title="Toggle theme"><span class="theme-toggle-icon" aria-hidden="true">☀</span></button>'
            . '</div></header>';
    }

    private function renderVersionSelector(array $documents): string
    {
        $html = '<label class="version-switcher" for="api-version-select"><span class="version-switcher-label">API</span>'
            . '<select id="api-version-select" aria-label="API version">';

        foreach ($documents as $document) {
            $version = (string)($document['version'] ?? 'v1');
            $slug = $this->versionSlug($version);
            $html .= '<option value="' . $this->e($slug) . '">' . $this->e(strtoupper($version)) . '</option>';
        }

        return $html . '</select></label>';
    }

    private function renderSidebar(string $nav, bool $versioned = false): string
    {
        $overview = $versioned
            ? ''
            : '<a class="nav-item nav-overview" href="#overview" data-nav-target="overview">Overview</a>';

        return '<aside class="sidebar" id="docs-sidebar">'
            . '<div class="sidebar-head">'
            . '<span class="sidebar-label">Navigation</span>'
            . '<div class="search-box"><input type="search" id="doc-search" placeholder="Search endpoints..." autocomplete="off"></div>'
            . '<div class="search-empty" id="search-empty">No matching endpoints found.</div>'
            . '</div>'
            . $overview
            . $nav
            . '</aside>';
    }

    private function renderContentToolbar(): string
    {
        return '<div class="content-toolbar">'
            . '<span class="content-toolbar-title">API Reference</span>'
            . '<div class="content-toolbar-actions">'
            . '<button type="button" class="tool-btn" data-expand-all>Expand all</button>'
            . '<button type="button" class="tool-btn" data-collapse-all>Collapse all</button>'
            . '</div></div>';
    }

    private function renderNav(array $document): string
    {
        $html = '';
        $grouped = [];

        foreach ($document['operations'] as $operation) {
            $grouped[(string)$operation['tag']][] = $operation;
        }

        foreach ($grouped as $tag => $operations) {
            $html .= '<div class="nav-group" data-nav-group>';
            $html .= '<button type="button" class="nav-group-toggle" aria-expanded="false" data-nav-toggle>'
                . '<span class="nav-group-chevron">›</span>'
                . '<span class="nav-group-title">' . $this->e($tag) . '</span>'
                . '<span class="tag-count">' . count($operations) . '</span>'
                . '</button>';
            $html .= '<div class="nav-group-items">';

            foreach ($operations as $operation) {
                $label = trim((string)($operation['summary'] ?? ''));
                if ($label === '') {
                    $label = trim((string)($operation['operationName'] ?? $operation['path']));
                }

                $html .= '<a class="nav-item" href="#' . $this->e((string)$operation['id']) . '" data-nav-target="' . $this->e((string)$operation['id']) . '" data-search="' . $this->e(strtolower($tag . ' ' . $label . ' ' . $operation['method'] . ' ' . $operation['summary'] . ' ' . $operation['path'])) . '">'
                    . $this->e($label)
                    . '</a>';
            }

            $html .= '</div></div>';
        }

        return $html;
    }

    private function renderDocument(array $document, bool $versioned = false, ?string $extraMarkdown = null): string
    {
        $version = (string)($document['version'] ?? 'v1');
        $overviewId = $versioned ? 'overview-' . $this->versionSlug($version) : 'overview';

        $html = '<section class="info-card app-hero" id="' . $this->e($overviewId) . '">'
            . '<div class="app-hero-head">'
            . '<div>'
            . '<h2>' . $this->e((string)$document['title']) . '</h2>'
            . '<p class="app-description">' . $this->e((string)$document['description']) . '</p>'
            . $this->renderCopyUrl($document)
            . $this->renderExtraMarkdown($extraMarkdown)
            . '</div></div>'
            . '<div class="stats-grid">'
            . $this->statItem('Operations', (string)($document['operation_count'] ?? count($document['operations'] ?? [])))
            . ($this->visible($document, 'generated_at') ? $this->statItem('Generated', (string)($document['generated_at'] ?? '')) : '')
            . '</div>'
            . '<div class="info-grid">'
            . $this->infoItem('App name', (string)($document['app_name'] ?? ''))
            . ($this->visible($document, 'package') ? $this->infoItem('Package', (string)$document['package']) : '')
            . $this->infoItem('Developer', (string)$document['developer'])
            . $this->infoItem('App version', (string)($document['app_version'] ?? ''))
            . $this->infoItem('API version', (string)$document['version'])
            . $this->infoItem('Language', (string)($document['app_lang'] ?? ''))
            . ($this->visible($document, 'theme') ? $this->infoItem('Theme', (string)($document['app_theme'] ?? '')) : '')
            . '</div>';

        if ($this->visible($document, 'global_flow') && !empty($document['global_flow'])) {
            $html .= '<p class="global-flow"><strong>Global flow:</strong> <code>' . $this->e(implode(', ', $document['global_flow'])) . '</code></p>';
        }

        $html .= '</section>';

        $grouped = [];
        foreach ($document['operations'] as $operation) {
            $grouped[(string)$operation['tag']][] = $operation;
        }

        $tagOrder = [];
        foreach ($document['tags'] ?? [] as $tag) {
            $name = (string)($tag['name'] ?? '');
            if ($name !== '') {
                $tagOrder[$name] = (string)($tag['description'] ?? '');
            }
        }

        foreach (array_keys($grouped) as $tagName) {
            if (!array_key_exists($tagName, $tagOrder)) {
                $tagOrder[$tagName] = '';
            }
        }

        foreach ($tagOrder as $tagName => $tagDescription) {
            if (empty($grouped[$tagName])) {
                continue;
            }

            $tagId = $this->tagSectionId($tagName, $version, $versioned);
            $count = count($grouped[$tagName]);
            $html .= '<section class="tag-section collapsible open" id="' . $this->e($tagId) . '" data-tag-section>';
            $html .= '<button type="button" class="section-toggle" data-section-toggle aria-expanded="true">'
                . '<span class="section-chevron">›</span>'
                . '<div class="section-toggle-text"><h3>' . $this->e($tagName) . '</h3>';

            if (trim($tagDescription) !== '') {
                $html .= '<p>' . $this->e($tagDescription) . '</p>';
            }

            $html .= '</div>'
                . '<span class="section-count">' . $count . ' endpoint' . ($count === 1 ? '' : 's') . '</span>'
                . '</button>';
            $html .= '<div class="section-body">';

            foreach ($grouped[$tagName] as $operation) {
                $html .= $this->renderOperation($document, $operation);
            }

            $html .= '</div></section>';
        }

        return $html;
    }

    private function renderExtraMarkdown(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return '<div class="md-content">' . (new PinDocMarkdownConverter())->convert($markdown) . '</div>';
    }

    private function renderCopyUrl(array $document): string
    {
        if (empty($document['app_url_explicit']) || trim((string)($document['app_url'] ?? '')) === '') {
            return '';
        }

        return $this->renderCopyUrlField((string)$document['app_url'], 'App URL');
    }

    private function renderCopyUrlField(string $url, string $label = 'URL', bool $compact = false): string
    {
        if (trim($url) === '') {
            return '';
        }

        $class = 'copy-url-bar' . ($compact ? ' copy-url-bar-compact' : '');

        return '<div class="' . $class . '">'
            . '<span class="copy-url-label">' . $this->e($label) . '</span>'
            . '<div class="copy-url-field">'
            . '<code class="copy-url-value">' . $this->e($url) . '</code>'
            . '<button class="copy-url-btn" type="button" data-copy="' . $this->e($url) . '">Copy</button>'
            . '</div></div>';
    }

    private function statItem(string $label, string $value): string
    {
        if ($value === '') {
            return '';
        }

        return '<div class="stat-item"><span>' . $this->e($label) . '</span><strong>' . $this->e($value) . '</strong></div>';
    }

    private function renderOperation(array $document, array $operation): string
    {
        $pathLabel = (string)($operation['operationName'] ?? $operation['path']);
        $displayPath = ($document['kind'] ?? 'rest') === 'graphql'
            ? '/graphql · ' . $pathLabel
            : (string)$operation['path'];

        $html = '<article class="operation" id="' . $this->e((string)$operation['id']) . '" data-search="' . $this->e(strtolower($displayPath . ' ' . $operation['summary'] . ' ' . $operation['method'])) . '">'
            . '<div class="op-header" data-toggle>'
            . '<span class="op-chevron">›</span>'
            . '<span class="method method-' . $this->e((string)$operation['method']) . '">' . $this->e((string)$operation['method']) . '</span>'
            . '<code class="op-path">' . $this->e($displayPath) . '</code>'
            . '<span class="op-summary">' . $this->e((string)$operation['summary']) . '</span>'
            . '</div>'
            . '<div class="op-body">';

        if (!empty($operation['deprecated'])) {
            $html .= '<p class="deprecated">Deprecated</p>';
        }

        if (!empty($operation['description'])) {
            $html .= '<p class="op-description">' . $this->e((string)$operation['description']) . '</p>';
        }

        $html .= $this->renderCopyUrlField(
            DocsAppUrlResolver::operationUrl($document, $operation),
            'Endpoint URL',
            true,
        );

        $html .= $this->renderMeta($document, $operation);
        $html .= '<div class="tabs">'
            . '<button class="tab-btn active" data-tab="params">Parameters</button>'
            . '<button class="tab-btn" data-tab="body">Request Body</button>'
            . '<button class="tab-btn" data-tab="responses">Responses</button>'
            . '<button class="tab-btn" data-tab="examples">Examples</button>'
            . '</div>';

        $html .= '<div class="tab-panel active" data-panel="params">' . $this->renderParameters($operation) . '</div>';
        $html .= '<div class="tab-panel" data-panel="body">' . $this->renderRequestBody($operation) . '</div>';
        $html .= '<div class="tab-panel" data-panel="responses">' . $this->renderResponses($operation) . '</div>';
        $html .= '<div class="tab-panel" data-panel="examples">' . $this->renderExamples($document, $operation) . '</div>';
        $html .= '</div></article>';

        return $html;
    }

    private function renderMeta(array $document, array $operation): string
    {
        if (!$this->visible($document, 'metadata_section')) {
            return $this->renderAuthBadge($document, $operation);
        }

        $meta = $operation['meta'] ?? [];
        $security = $operation['security'] ?? [];
        $chips = [];

        if ($this->visible($document, 'route_name')) {
            $value = trim((string)($meta['route_name'] ?? ''));
            if ($value !== '') {
                $chips[] = $this->metaChip('Route name', $value);
            }
        }

        if ($this->visible($document, 'handler')) {
            $value = trim((string)($meta['controller'] ?? ''));
            if ($value !== '') {
                $chips[] = $this->metaChip('Handler', $value);
            }
        }

        if ($this->visible($document, 'request_class')) {
            $value = trim((string)($meta['request'] ?? ''));
            if ($value !== '') {
                $chips[] = $this->metaChip('Form request', $value);
            }
        }

        if ($this->visible($document, 'resource_class')) {
            $value = trim((string)($meta['resource'] ?? ''));
            if ($value !== '') {
                $chips[] = $this->metaChip('Resource', $value);
            }
        }

        if ($this->visible($document, 'graphql_class')) {
            $value = trim((string)($meta['controller'] ?? ''));
            if ($value !== '') {
                $chips[] = $this->metaChip('Class', $value);
            }
        }

        if ($this->visible($document, 'flow') && !empty($security['flow'])) {
            $chips[] = $this->metaChip('Flow', implode(', ', $security['flow']));
        }

        foreach (['permission' => 'permission', 'auth' => 'auth_details', 'rate_limit' => 'rate_limit'] as $key => $flag) {
            if (!$this->visible($document, $flag)) {
                continue;
            }

            $value = trim((string)($security[$key] ?? ''));
            if ($value !== '') {
                $labels = ['permission' => 'Permission', 'auth' => 'Auth', 'rate_limit' => 'Rate limit'];
                $chips[] = $this->metaChip($labels[$key], $value);
            }
        }

        $authBadge = $this->renderAuthBadge($document, $operation, false);
        if ($authBadge !== '') {
            $chips[] = $authBadge;
        }

        if ($chips === []) {
            return '';
        }

        return '<div class="meta-grid">' . implode('', $chips) . '</div>';
    }

    private function renderAuthBadge(array $document, array $operation, bool $wrap = true): string
    {
        $security = $operation['security'] ?? [];

        if (!$this->visible($document, 'auth_required_badge') || empty($security['authenticated'])) {
            return '';
        }

        if ($this->visible($document, 'flow') && !empty($security['flow'])) {
            return '';
        }

        $chip = $this->metaChip('Access', 'Authentication required');

        return $wrap ? '<div class="meta-grid">' . $chip . '</div>' : $chip;
    }

    private function metaChip(string $label, string $value): string
    {
        return '<div class="meta-chip"><span>' . $this->e($label) . '</span><code>' . $this->e($value) . '</code></div>';
    }

    private function renderParameters(array $operation): string
    {
        $parameters = $operation['parameters'] ?? [];

        if ($parameters === []) {
            return '<p class="empty">No parameters.</p>';
        }

        $html = '<table><thead><tr><th>Name</th><th>In</th><th>Type</th><th>Required</th><th>Description</th><th>Example</th></tr></thead><tbody>';

        foreach ($parameters as $parameter) {
            if (!is_array($parameter)) {
                continue;
            }

            $html .= '<tr>'
                . '<td><code>' . $this->e((string)($parameter['name'] ?? '')) . '</code></td>'
                . '<td>' . $this->e((string)($parameter['in'] ?? 'query')) . '</td>'
                . '<td>' . $this->e((string)($parameter['type'] ?? 'string')) . '</td>'
                . '<td>' . (!empty($parameter['required']) ? '<span class="required">required</span>' : 'optional') . '</td>'
                . '<td>' . $this->e((string)($parameter['description'] ?? '')) . '</td>'
                . '<td><code>' . $this->e($this->stringify($parameter['example'] ?? null)) . '</code></td>'
                . '</tr>';
        }

        return $html . '</tbody></table>';
    }

    private function renderRequestBody(array $operation): string
    {
        $body = $operation['requestBody'] ?? null;

        if ($body === null) {
            return '<p class="empty">No request body.</p>';
        }

        $html = '<p>' . $this->e((string)($body['description'] ?? 'Request payload')) . '</p>';

        if (!empty($body['schema'])) {
            $html .= '<h4>Schema</h4>' . $this->codeBlock($body['schema']);
        }

        if (!empty($body['example'])) {
            $html .= '<h4>Example</h4>' . $this->codeBlock($body['example']);
        }

        return $html;
    }

    private function renderResponses(array $operation): string
    {
        $responses = $operation['responses'] ?? [];

        if ($responses === []) {
            return '<p class="empty">No documented responses.</p>';
        }

        $html = '';

        foreach ($responses as $response) {
            if (!is_array($response)) {
                continue;
            }

            $html .= '<h4>' . $this->e((string)($response['status'] ?? 200)) . ' - ' . $this->e((string)($response['description'] ?? 'Success')) . '</h4>';
            if (!empty($response['example'])) {
                $html .= $this->codeBlock($response['example']);
            }
        }

        return $html;
    }

    private function renderExamples(array $document, array $operation): string
    {
        $examples = $operation['examples'] ?? [];
        $path = DocsAppUrlResolver::operationUrl($document, $operation);
        $html = '';

        if (!empty($examples['curl'])) {
            $html .= '<h4>cURL</h4>' . $this->codeBlock(str_replace('{{path}}', $path, (string)$examples['curl']), true);
        }

        if (!empty($examples['fetch'])) {
            $html .= '<h4>JavaScript fetch</h4>' . $this->codeBlock(str_replace('{{path}}', $path, (string)$examples['fetch']), true);
        }

        if (!empty($examples['php']) && $this->visible($document, 'php_examples')) {
            $html .= '<h4>PHP</h4>' . $this->codeBlock(str_replace('{{path}}', $path, (string)$examples['php']), true);
        }

        foreach ($examples['graphql'] ?? [] as $example) {
            $html .= '<h4>GraphQL</h4>' . $this->codeBlock((string)$example, true);
        }

        if ($html === '') {
            return '<p class="empty">No examples.</p>';
        }

        return $html;
    }

    private function codeBlock(mixed $value, bool $raw = false): string
    {
        $content = $raw && is_string($value)
            ? $value
            : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return '<div class="code-block"><button class="copy-btn" type="button">Copy</button><pre><code>'
            . $this->e((string)$content)
            . '</code></pre></div>';
    }

    private function infoItem(string $label, string $value): string
    {
        return '<div class="info-item"><strong>' . $this->e($label) . '</strong><code>' . $this->e($value) . '</code></div>';
    }

    private function stringify(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function hasMultipleApiVersions(array $documents): bool
    {
        if (count($documents) < 2) {
            return false;
        }

        $versions = [];
        foreach ($documents as $document) {
            $versions[$this->versionSlug((string)($document['version'] ?? ''))] = true;
        }

        return count($versions) > 1;
    }

    private function defaultVersionSlug(array $documents): string
    {
        return $this->versionSlug((string)($documents[0]['version'] ?? 'v1'));
    }

    private function versionSlug(string $version): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower(trim($version, '/')));

        return $slug !== '' ? $slug : 'v1';
    }

    private function tagSectionId(string $tagName, string $version = '', bool $versioned = false): string
    {
        $base = 'tag-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($tagName));

        if ($versioned && $version !== '') {
            return $this->versionSlug($version) . '-' . $base;
        }

        return $base;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function script(): string
    {
        return <<<'JS'
<script>
const body = document.body;
const searchInput = document.getElementById('doc-search');
const searchEmpty = document.getElementById('search-empty');
const sidebar = document.getElementById('docs-sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');
const backToTop = document.getElementById('back-to-top');
const themeToggle = document.getElementById('theme-toggle');
const root = document.documentElement;
const THEME_KEY = 'pindoc-theme';

function applyTheme(theme) {
    const next = theme === 'light' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem(THEME_KEY, next); } catch (e) {}
    if (themeToggle) {
        const icon = themeToggle.querySelector('.theme-toggle-icon');
        if (icon) icon.textContent = next === 'dark' ? '☀' : '🌙';
        themeToggle.title = next === 'dark' ? 'Switch to light theme' : 'Switch to dark theme';
    }
}

if (themeToggle) {
    applyTheme(root.getAttribute('data-theme') || 'dark');
    themeToggle.addEventListener('click', () => {
        applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
}

const versionSelect = document.getElementById('api-version-select');
const API_VERSION_KEY = 'pindoc-api-version';

function activeVersionSlug() {
    if (!body.dataset.multiVersion) {
        return body.dataset.defaultVersion || '';
    }
    if (versionSelect) {
        return versionSelect.value;
    }
    const visible = document.querySelector('.doc-version:not(.is-hidden)');
    return visible ? visible.dataset.docVersion : (body.dataset.defaultVersion || '');
}

function applyApiVersion(slug, persist) {
    if (!slug) return;

    document.querySelectorAll('[data-doc-version]').forEach((element) => {
        if (!element.classList.contains('sidebar-version') && !element.classList.contains('doc-version')) {
            return;
        }
        element.classList.toggle('is-hidden', element.dataset.docVersion !== slug);
    });

    if (versionSelect && versionSelect.value !== slug) {
        versionSelect.value = slug;
    }

    if (persist !== false) {
        const storageKey = API_VERSION_KEY + ':' + (body.dataset.docPackage || 'app');
        try { localStorage.setItem(storageKey, slug); } catch (e) {}
    }

    if (searchInput && searchInput.value.trim() !== '') {
        filterDocs(searchInput.value);
    }

    updateActiveNav();
}

if (versionSelect) {
    const storageKey = API_VERSION_KEY + ':' + (body.dataset.docPackage || 'app');
    let initial = body.dataset.defaultVersion || versionSelect.value;
    try {
        const saved = localStorage.getItem(storageKey);
        if (saved && versionSelect.querySelector('option[value="' + saved + '"]')) {
            initial = saved;
        }
    } catch (e) {}
    applyApiVersion(initial, false);
    versionSelect.addEventListener('change', () => applyApiVersion(versionSelect.value));
}

function setOpen(element, open, toggleSelector) {
    if (!element) return;
    element.classList.toggle('open', open);
    const toggle = element.querySelector(toggleSelector);
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
}

const SCROLL_OFFSET = 80;

function scrollToElement(el) {
    if (!el) return;
    const top = el.getBoundingClientRect().top + window.scrollY - SCROLL_OFFSET;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
}

function expandTargetSections(el) {
    if (!el) return;

    const tagSection = el.matches('[data-tag-section]') ? el : el.closest('[data-tag-section]');
    if (tagSection) {
        setOpen(tagSection, true, '[data-section-toggle]');
    }

    const operation = el.matches('.operation') ? el : el.closest('.operation');
    if (operation) {
        setOpen(operation, true, '[data-toggle]');
    }
}

function navigateToId(id, sidebarGroup) {
    const el = document.getElementById(id);
    if (!el) return;

    expandTargetSections(el);

    if (sidebarGroup) {
        setOpen(sidebarGroup, true, '[data-nav-toggle]');
    }

    body.classList.remove('sidebar-open');

    requestAnimationFrame(() => {
        scrollToElement(el);
        setTimeout(() => scrollToElement(el), 180);
        updateActiveNav();
    });
}

document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();
        const group = button.closest('[data-nav-group]');
        setOpen(group, !group.classList.contains('open'), '[data-nav-toggle]');
    });
});

document.querySelectorAll('a[data-nav-target]').forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        const id = (link.getAttribute('href') || '').replace('#', '');
        const group = link.closest('[data-nav-group]');
        navigateToId(id, group);
    });
});

document.querySelectorAll('[data-section-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const section = button.closest('[data-tag-section]');
        setOpen(section, !section.classList.contains('open'), '[data-section-toggle]');
    });
});

document.querySelectorAll('[data-toggle]').forEach((header) => {
    header.addEventListener('click', () => {
        const operation = header.closest('.operation');
        setOpen(operation, !operation.classList.contains('open'), '[data-toggle]');
    });
});

document.querySelectorAll('.operation .tab-btn').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.stopPropagation();
        const root = button.closest('.operation');
        root.querySelectorAll('.tab-btn').forEach((item) => item.classList.remove('active'));
        root.querySelectorAll('.tab-panel').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
        root.querySelector('[data-panel="' + button.dataset.tab + '"]').classList.add('active');
    });
});

function copyText(button, value, resetLabel) {
    navigator.clipboard.writeText(value).then(() => {
        button.textContent = 'Copied';
        setTimeout(() => button.textContent = resetLabel, 1200);
    });
}

document.querySelectorAll('.copy-url-btn').forEach((button) => {
    button.addEventListener('click', () => copyText(button, button.dataset.copy || '', 'Copy'));
});

document.querySelectorAll('.copy-btn').forEach((button) => {
    button.addEventListener('click', () => {
        const code = button.parentElement.querySelector('code').innerText;
        copyText(button, code, 'Copy');
    });
});

function filterDocs(query) {
    query = query.trim().toLowerCase();
    let visibleNavCount = 0;
    const versionSlug = activeVersionSlug();
    const sidebarRoot = document.querySelector('.sidebar-version[data-doc-version="' + versionSlug + '"]') || sidebar;

    sidebarRoot.querySelectorAll('.nav-group').forEach((group) => {
        let groupVisible = 0;
        group.querySelectorAll('.nav-item:not(.nav-overview)').forEach((item) => {
            const match = !query || (item.dataset.search || '').includes(query);
            item.style.display = match ? 'block' : 'none';
            if (match) groupVisible++;
        });
        group.style.display = groupVisible > 0 || !query ? 'block' : 'none';
        if (groupVisible > 0) setOpen(group, true, '[data-nav-toggle]');
        visibleNavCount += groupVisible;
    });

    const contentRoot = document.querySelector('.doc-version[data-doc-version="' + versionSlug + '"]') || document.querySelector('.content');
    contentRoot.querySelectorAll('.operation').forEach((item) => {
        const match = !query || (item.dataset.search || '').includes(query);
        item.style.display = match ? 'block' : 'none';
        if (match && query) setOpen(item, true, '[data-toggle]');
    });

    contentRoot.querySelectorAll('[data-tag-section]').forEach((section) => {
        const visibleOps = section.querySelectorAll('.operation:not([style*="display: none"])');
        section.style.display = visibleOps.length > 0 || !query ? 'block' : 'none';
        if (visibleOps.length > 0 && query) setOpen(section, true, '[data-section-toggle]');
    });

    if (searchEmpty) {
        searchEmpty.classList.toggle('visible', query !== '' && visibleNavCount === 0);
    }
}

if (searchInput) {
    searchInput.addEventListener('input', () => filterDocs(searchInput.value));
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            searchInput.value = '';
            filterDocs('');
        }
    });
}

document.querySelectorAll('[data-expand-all]').forEach((button) => {
    button.addEventListener('click', () => {
        const root = document.querySelector('.doc-version[data-doc-version="' + activeVersionSlug() + '"]') || document.querySelector('.content');
        root.querySelectorAll('[data-tag-section], .operation').forEach((el) => {
            setOpen(el, true, el.matches('.operation') ? '[data-toggle]' : '[data-section-toggle]');
        });
    });
});

document.querySelectorAll('[data-collapse-all]').forEach((button) => {
    button.addEventListener('click', () => {
        const root = document.querySelector('.doc-version[data-doc-version="' + activeVersionSlug() + '"]') || document.querySelector('.content');
        root.querySelectorAll('[data-tag-section], .operation').forEach((el) => {
            setOpen(el, false, el.matches('.operation') ? '[data-toggle]' : '[data-section-toggle]');
        });
    });
});

if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => body.classList.toggle('sidebar-open'));
    body.addEventListener('click', (event) => {
        if (!body.classList.contains('sidebar-open')) return;
        if (sidebar.contains(event.target) || sidebarToggle.contains(event.target)) return;
        body.classList.remove('sidebar-open');
    });
}

const navLinks = Array.from(document.querySelectorAll('a[data-nav-target]'));
let navSections = [];

function rebuildNavSections() {
    const versionSlug = activeVersionSlug();
    const sidebarRoot = document.querySelector('.sidebar-version[data-doc-version="' + versionSlug + '"]') || sidebar;
    const links = sidebarRoot ? Array.from(sidebarRoot.querySelectorAll('a[data-nav-target]')) : navLinks;

    navSections = links.map((link) => {
        const id = link.getAttribute('href').replace('#', '');
        return { link, el: document.getElementById(id) };
    }).filter((item) => item.el);
}

function updateActiveNav() {
    rebuildNavSections();
    let current = navSections[0];
    const offset = 120;
    navSections.forEach((item) => {
        if (item.el.getBoundingClientRect().top - offset <= 0) current = item;
    });
    navLinks.forEach((link) => link.classList.remove('active'));
    if (current) current.link.classList.add('active');
}

window.addEventListener('scroll', () => {
    updateActiveNav();
    if (backToTop) backToTop.classList.toggle('visible', window.scrollY > 500);
}, { passive: true });

if (backToTop) {
    backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

updateActiveNav();
</script>
JS;
    }
}

