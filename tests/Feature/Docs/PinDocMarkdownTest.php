<?php

use Pinoox\PinDoc\PinDocMarkdownConverter;

it('converts markdown headings lists and code blocks to html', function () {
    $converter = new PinDocMarkdownConverter();

    $html = $converter->convert(<<<'MD'
## Custom section

Hello **world**.

- first item
- second item

```json
{"ok": true}
```
MD);

    expect($html)
        ->toContain('<h2>Custom section</h2>')
        ->toContain('<strong>world</strong>')
        ->toContain('<ul><li>first item</li><li>second item</li></ul>')
        ->toContain('{&quot;ok&quot;: true}');
});

it('extracts pindoc extra blocks from markdown files', function () {
    $converter = new PinDocMarkdownConverter();

    $extra = $converter->extractExtraBlocks(<<<'MD'
# API

<!-- pindoc:extra -->
## Notes
Keep this part.
<!-- /pindoc:extra -->
MD);

    expect($extra)->toContain('## Notes')
        ->toContain('Keep this part.');
});

it('merges markdown extras into api html output', function () {
    apiSystemWriteTestApp('com_test_api_routes', [
        'routes/api.php' => <<<'PHP'
<?php

return [
    'version' => 'v1',
    'prefix' => 'catalog',
    'docs' => [
        'markdown' => 'docs/api-extra.md',
    ],
    'routes' => [
        [
            'method' => 'GET',
            'uri' => '/items',
            'action' => ['CatalogController', 'index'],
            'name' => 'items.index',
        ],
    ],
];
PHP,
        'docs/api-extra.md' => "## Custom docs\n\nExtra **content** here.\n",
    ]);
    \Pinoox\Portal\App\AppEngine::__rebuild();

    $html = (new \Pinoox\PinDoc\PinDocHtmlBuilder())->fromRestApi('com_test_api_routes');

    expect($html)->toContain('class="md-content"')
        ->toContain('<h2>Custom docs</h2>')
        ->toContain('<strong>content</strong>');
});

