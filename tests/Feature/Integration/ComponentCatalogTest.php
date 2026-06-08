<?php

use Composer\Autoload\ClassLoader;

it('declares an autoloadable symbol or data payload for each component file', function (string $file, ?string $symbol, ?string $kind) {
    expect(is_file($file))->toBeTrue();

    if ($symbol === null) {
        expect(require $file)->toBeArray();
        return;
    }

    $autoloadFile = componentCatalogClassLoader()->findFile($symbol);

    expect($kind)->toBeIn(['class', 'interface', 'trait', 'enum'])
        ->and(componentCatalogNormalizePath($autoloadFile))->toBe($file);
})->with(componentCatalogDataset());

function componentCatalogDataset(): array
{
    $basePath = str_replace('\\', '/', testProjectRoot());
    $componentPath = $basePath . '/pincore/Component';
    $files = componentCatalogPhpFiles($componentPath);
    $dataset = [];

    foreach ($files as $file) {
        [$symbol, $kind] = componentCatalogReadSymbol($file);
        $key = $symbol ?? str_replace($basePath . '/', '', $file);
        $dataset[$key] = [$file, $symbol, $kind];
    }

    return $dataset;
}

function componentCatalogPhpFiles(string $directory): array
{
    $files = [];
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($items as $item) {
        if ($item->isFile() && $item->getExtension() === 'php') {
            $files[] = str_replace('\\', '/', $item->getPathname());
        }
    }

    sort($files);

    return $files;
}

function componentCatalogReadSymbol(string $file): array
{
    $tokens = token_get_all(file_get_contents($file));
    $namespace = '';

    foreach ($tokens as $index => $token) {
        if (is_array($token) && $token[0] === T_NAMESPACE) {
            $namespace = componentCatalogReadNamespace($tokens, $index);
            continue;
        }

        $kind = componentCatalogTokenKind($token, $tokens, $index);
        if ($kind !== null) {
            return [$namespace . '\\' . componentCatalogReadName($tokens, $index), $kind];
        }
    }

    return [null, null];
}

function componentCatalogTokenKind(mixed $token, array $tokens = [], int $index = 0): ?string
{
    if (!is_array($token)) {
        return null;
    }

    if ($token[0] === T_CLASS) {
        for ($i = $index - 1; $i >= 0; $i--) {
            $previous = $tokens[$i];
            if (is_array($previous) && in_array($previous[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if ($previous === ':' || (is_array($previous) && $previous[0] === T_DOUBLE_COLON)) {
                return null;
            }

            break;
        }
    }

    return match ($token[0]) {
        T_CLASS => 'class',
        T_INTERFACE => 'interface',
        T_TRAIT => 'trait',
        defined('T_ENUM') ? T_ENUM : -1 => 'enum',
        default => null,
    };
}

function componentCatalogReadNamespace(array $tokens, int $index): string
{
    $parts = [];

    for ($i = $index + 1, $count = count($tokens); $i < $count; $i++) {
        $token = $tokens[$i];

        if ($token === ';' || $token === '{') {
            break;
        }

        if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
            $parts[] = $token[1];
        }
    }

    return implode('', $parts);
}

function componentCatalogReadName(array $tokens, int $index): string
{
    for ($i = $index + 1, $count = count($tokens); $i < $count; $i++) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
            return $tokens[$i][1];
        }
    }

    throw new RuntimeException('Unable to read symbol name.');
}

function componentCatalogClassLoader(): ClassLoader
{
    foreach (spl_autoload_functions() as $autoloadFunction) {
        if (is_array($autoloadFunction) && $autoloadFunction[0] instanceof ClassLoader) {
            return $autoloadFunction[0];
        }
    }

    $loader = require testProjectRoot() . '/vendor/autoload.php';

    if ($loader instanceof ClassLoader) {
        return $loader;
    }

    throw new RuntimeException('Composer class loader was not registered.');
}

function componentCatalogNormalizePath(string|false $path): string
{
    if ($path === false) {
        return '';
    }

    $realPath = realpath($path);

    return str_replace('\\', '/', $realPath !== false ? $realPath : $path);
}

