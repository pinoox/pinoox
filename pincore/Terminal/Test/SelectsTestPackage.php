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
        return $this->resolvePackageRequired($input, $output, $io, [
            'sectionTitle' => 'Packages with tests',
            'includeTestColumn' => true,
        ]);
    }

    protected function testPath(string $package, ?string $suite = null): string
    {
        $path = $package === 'pincore'
            ? path('~/tests')
            : AppEngine::path($package) . '/tests';

        return $suite ? $path . '/' . $suite : $path;
    }
}
