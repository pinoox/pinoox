<?php

use Pinoox\Component\Template\Seo\SeoMeta;
use Pinoox\Portal\View;

beforeEach(function () {
    Pinoox\Component\Test\AppTestKit::boot();
});

it('shares seo meta through view portal', function () {
    View::shareSeo([
        'title' => 'Products',
        'description' => 'Catalog page',
    ]);

    $shared = View::get('_seo');

    expect($shared)->toBeInstanceOf(SeoMeta::class)
        ->and($shared->title)->toBe('Products');
});

it('renders seo_tags helper from shared meta', function () {
    share_seo([
        'title' => 'About',
        'canonical' => 'https://example.com/about',
    ]);

    $html = seo_tags();

    expect($html)
        ->toContain('<title>About</title>')
        ->toContain('rel="canonical"');
});

it('renders vite_tags helper without throwing', function () {
    $themePath = sys_get_temp_dir() . '/pinoox-vite-tags-' . uniqid();
    mkdir($themePath . '/dist/.vite', 0777, true);
    file_put_contents($themePath . '/dist/.vite/manifest.json', json_encode([
        'src/main.js' => ['file' => 'assets/main.js', 'isEntry' => true],
    ]));

    // vite_tags uses active theme; verify helper exists and returns string for hot file
    file_put_contents($themePath . '/dist/hot', 'http://127.0.0.1:5173');

    $helper = new Pinoox\Component\Helpers\ViteHelper($themePath);
    $tags = implode('', $helper->vite('src/main.js'));

    expect($tags)->toContain('@vite/client');

    @unlink($themePath . '/dist/hot');
    @unlink($themePath . '/dist/.vite/manifest.json');
    @rmdir($themePath . '/dist/.vite');
    @rmdir($themePath . '/dist');
    @rmdir($themePath);
});

