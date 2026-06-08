<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$feature = $root . '/tests/Feature';

$moves = [
    'ServeAppBindingTest.php' => 'Server',
    'ServeCommandTest.php' => 'Server',
    'AppRouterSystemTest.php' => 'Routing',
    'RouteNamingTest.php' => 'Routing',
    'RouterSystemTest.php' => 'Routing',
    'QueryRouteCanonicalTest.php' => 'Routing',
    'RouteContextResolverTest.php' => 'Routing',
    'ActionSystemTest.php' => 'Routing',
    'PathUrlSystemTest.php' => 'Routing',
    'HttpRequestTest.php' => 'Http',
    'FormRequestTest.php' => 'Http',
    'HttpApiResponseTest.php' => 'Http',
    'ApiSystemTest.php' => 'Http',
    'ApiDocInferenceTest.php' => 'Http',
    'ManagerApiEnvelopeTest.php' => 'Http',
    'InstallerApiEnvelopeTest.php' => 'Http',
    'AppBootModeSystemTest.php' => 'App',
    'AppDependencySystemTest.php' => 'App',
    'AppEventSystemTest.php' => 'App',
    'AppHmvcSystemTest.php' => 'App',
    'AppRegistryTest.php' => 'App',
    'AppTestKitTest.php' => 'App',
    'AppCacheSystemTest.php' => 'Cache',
    'ConfigTest.php' => 'Config',
    'SystemConfigTest.php' => 'Config',
    'EnvFileTest.php' => 'Config',
    'EnvSensitivePinkerTest.php' => 'Config',
    'DatabaseConnectionTest.php' => 'Database',
    'DatabaseCredentialsSyncTest.php' => 'Database',
    'InstallerDatabaseTest.php' => 'Database',
    'InstallerMigrationFlowTest.php' => 'Installer',
    'InstallerProvisionTest.php' => 'Installer',
    'InstallerRouteDebugTest.php' => 'Installer',
    'InstallerSetupServiceTest.php' => 'Installer',
    'ThemeContextSystemTest.php' => 'Theme',
    'ThemeInheritanceTest.php' => 'Theme',
    'ThemeManifestTest.php' => 'Theme',
    'TemplatePortalTest.php' => 'Theme',
    'TemplateSystemTest.php' => 'Theme',
    'ExceptionContextTest.php' => 'Debug',
    'ExceptionHintResolverTest.php' => 'Debug',
    'TraceFrameClassifierTest.php' => 'Debug',
    'PortalContextResolverTest.php' => 'Debug',
    'KernelSystemTest.php' => 'Kernel',
    'CorePathTest.php' => 'Kernel',
    'StorageFilesystemTest.php' => 'Storage',
    'PinDocMarkdownTest.php' => 'Docs',
    'DocsAppUrlResolverTest.php' => 'Docs',
    'TransportScenarioTest.php' => 'Integration',
    'ComponentCatalogTest.php' => 'Integration',
    'PortalCatalogTest.php' => 'Integration',
    'PinkerTest.php' => 'Package',
    'PinxSystemTest.php' => 'Package',
    'ScheduleSystemTest.php' => 'Schedule',
    'RuntimeModeSystemTest.php' => 'Runtime',
    'PermissionSystemTest.php' => 'Auth',
    'UserSystemTest.php' => 'Auth',
    'LangSystemTest.php' => 'Lang',
    'LogSystemTest.php' => 'Log',
    'DateSystemTest.php' => 'Date',
    'RedisSystemTest.php' => 'Redis',
    'PatchCommandTest.php' => 'Cli',
    'DepsCommandTest.php' => 'Cli',
    'DepsOutputFilterTest.php' => 'Cli',
];

foreach ($moves as $file => $dir) {
    $targetDir = $feature . '/' . $dir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $src = $feature . '/' . $file;
    $dst = $targetDir . '/' . $file;

    if (!is_file($src)) {
        continue;
    }

    if (is_file($dst)) {
        unlink($dst);
    }

    rename($src, $dst);
}

$supportSrc = $feature . '/Support/KernelSampleService.php';
$supportDst = $root . '/tests/Support/KernelSampleService.php';
if (is_file($supportSrc)) {
    if (!is_dir(dirname($supportDst))) {
        mkdir(dirname($supportDst), 0777, true);
    }
    if (is_file($supportDst)) {
        unlink($supportDst);
    }
    rename($supportSrc, $supportDst);
}

@unlink($feature . '/PinooxJsRouteTest.php');

$skipDirs = ['Portal', 'Support', 'Server'];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($feature, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
        continue;
    }

    $relativeDir = str_replace('\\', '/', substr(dirname($fileInfo->getPathname()), strlen($feature) + 1));
    $top = explode('/', $relativeDir)[0] ?? '';

    if (in_array($top, $skipDirs, true) || $top === '' || str_contains($relativeDir, '/')) {
        if ($top === 'Portal') {
            continue;
        }
        if ($relativeDir !== $top) {
            continue;
        }
    }

    if (in_array($top, ['Portal'], true)) {
        continue;
    }

    $path = $fileInfo->getPathname();
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    $updated = str_replace('dirname(__DIR__, 2)', 'testProjectRoot()', $content);
    $updated = preg_replace(
        "#dirname\\(__DIR__\\) \\. '/Fixtures/([^']+)'#",
        "testFixtures('$1')",
        $updated,
    ) ?? $updated;
    $updated = preg_replace(
        '#dirname\\(__DIR__\\) \\. "/Fixtures/([^"]+)"#',
        'testFixtures(\'$1\')',
        $updated,
    ) ?? $updated;

    if ($updated !== $content) {
        file_put_contents($path, $updated);
        echo "fixed paths: {$relativeDir}/{$fileInfo->getFilename()}\n";
    }
}

echo "done\n";
