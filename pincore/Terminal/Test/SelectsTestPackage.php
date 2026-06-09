<?php

namespace Pinoox\Terminal\Test;

use Pinoox\Portal\App\AppEngine;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait SelectsTestPackage
{
    use SelectsPackage;

    protected function resolvePackage(InputInterface $input, OutputInterface $output, SymfonyStyle $io): string
    {
        if ($input->getOption('all')) {
            return 'all';
        }

        return $this->resolvePackageRequired($input, $output, $io, [
            'sectionTitle' => 'Packages with tests',
            'includeTestColumn' => true,
            'allowAll' => true,
            'default' => 'all',
            'selectionHint' => 'Press Ctrl+C to exit without running tests',
        ]);
    }

    /**
     * @return list<string> Absolute test directory paths (platform + apps with tests/)
     */
    protected function allTestPaths(InputInterface $input, string $suite = ''): array
    {
        $paths = [];

        if ($suite === '') {
            $platformPath = $this->resolveTestPath('platform', $input, $suite);
            if (is_dir($platformPath)) {
                $paths[] = $platformPath;
            }
        }

        foreach (array_keys(AppEngine::all()) as $package) {
            $path = $this->resolveTestPath($package, $input, '');
            if (is_dir($path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    protected function testPath(string $package, ?string $suite = null): string
    {
        if ($package === 'all') {
            $package = 'platform';
        }

        $path = $package === 'platform'
            ? path('~/tests')
            : AppEngine::path($package) . '/tests';

        return $suite ? $path . '/' . $suite : $path;
    }
}

