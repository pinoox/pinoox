<?php

it('declares a valid portal contract for each portal file', function (string $file, string $class) {
    $contract = portalCatalogReadContract($file);

    expect(is_file($file))->toBeTrue()
        ->and(portalCatalogExpectedClassFromFile($file))->toBe($class)
        ->and($contract['extends'])->toBe('Portal')
        ->and($contract['methods'])->toContain('__name');
})->with(portalCatalogDataset());

function portalCatalogDataset(): array
{
    $basePath = str_replace('\\', '/', testProjectRoot());
    $portalPath = $basePath . '/pincore/Portal';
    $files = portalCatalogPhpFiles($portalPath);
    $dataset = [];

    foreach ($files as $file) {
        $class = portalCatalogSymbolFromFile($file);
        $dataset[$class] = [$file, $class];
    }

    return $dataset;
}

function portalCatalogPhpFiles(string $directory): array
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

function portalCatalogSymbolFromFile(string $file): string
{
    $symbol = portalCatalogReadSymbol($file);

    if ($symbol === null) {
        throw new RuntimeException('Portal file does not declare a class: ' . $file);
    }

    return $symbol;
}

function portalCatalogReadSymbol(string $file): ?string
{
    $tokens = token_get_all(file_get_contents($file));
    $namespace = '';

    foreach ($tokens as $index => $token) {
        if (is_array($token) && $token[0] === T_NAMESPACE) {
            $namespace = portalCatalogReadNamespace($tokens, $index);
            continue;
        }

        if (is_array($token) && $token[0] === T_CLASS) {
            return $namespace . '\\' . portalCatalogReadName($tokens, $index);
        }
    }

    return null;
}

function portalCatalogReadContract(string $file): array
{
    $tokens = token_get_all(file_get_contents($file));
    $extends = null;
    $methods = [];

    foreach ($tokens as $index => $token) {
        if (is_array($token) && $token[0] === T_EXTENDS) {
            $extends = portalCatalogReadName($tokens, $index);
        }

        if (is_array($token) && $token[0] === T_FUNCTION) {
            $methods[] = portalCatalogReadName($tokens, $index);
        }
    }

    return [
        'extends' => $extends,
        'methods' => $methods,
    ];
}

function portalCatalogReadNamespace(array $tokens, int $index): string
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

function portalCatalogReadName(array $tokens, int $index): string
{
    for ($i = $index + 1, $count = count($tokens); $i < $count; $i++) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
            return $tokens[$i][1];
        }
    }

    throw new RuntimeException('Unable to read class name.');
}

function portalCatalogExpectedClassFromFile(string $file): string
{
    $basePath = str_replace('\\', '/', testProjectRoot()) . '/pincore/';
    $relativePath = substr(str_replace('\\', '/', $file), strlen($basePath), -4);

    return 'Pinoox\\' . str_replace('/', '\\', $relativePath);
}

