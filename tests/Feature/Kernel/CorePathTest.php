<?php

use Pinoox\Component\Package\Engine\EngineInterface;
use Pinoox\Component\Package\Parser\NameParser;
use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Path\Path;
use Pinoox\Support\SystemApp;

it('resolves the pincore path through the central core path constant', function () {
    $path = new Path(corePathTestNormalize(testProjectRoot()), new NameParser(), new CorePathTestEngine(), null);
    $corePath = defined('PINOOX_CORE_PATH')
        ? corePathTestNormalize(PINOOX_CORE_PATH)
        : corePathTestNormalize(testProjectRoot() . '/pincore');

    expect($path->get('~pincore'))->toBe($corePath)
        ->and($path->get('~pincore/config/app/source.config.php'))
        ->toBe($corePath . '/config/app/source.config.php');
});

it('resolves the system app path through the system alias', function () {
    $basePath = corePathTestNormalize(testProjectRoot());
    $path = new Path($basePath, new NameParser(), new CorePathTestEngine(), null);

    expect($path->get('~system'))->toBe($basePath . '/pincore/config')
        ->and($path->get('~pincore/lang/en/file.lang.php'))
        ->toBe($basePath . '/pincore/lang/en/file.lang.php');
});

function corePathTestNormalize(string $path): string
{
    return rtrim(str_replace('\\', '/', $path), '/');
}

class CorePathTestEngine implements EngineInterface
{
    public function config(string|ReferenceInterface $packageName): \Pinoox\Component\Store\Config\ConfigInterface
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function lang(string|ReferenceInterface $packageName): \Pinoox\Component\Translator\Translator
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function router(string|ReferenceInterface $packageName, string $path = ''): \Pinoox\Component\Router\Router
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function exists(string|ReferenceInterface $packageName): bool
    {
        return false;
    }

    public function stable(string|ReferenceInterface $packageName): bool
    {
        return false;
    }

    public function supports(string|ReferenceInterface $packageName): bool
    {
        return false;
    }

    public function path(string|ReferenceInterface $packageName, string $path = ''): string
    {
        return '';
    }
}

