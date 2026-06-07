<?php

namespace Pinoox\Terminal\Migrate;

use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait SelectsMigrationPackage
{
    use SelectsPackage;

    protected function resolvePackage(InputInterface $input, OutputInterface $output, SymfonyStyle $io): string
    {
        return $this->resolvePackageRequired($input, $output, $io, [
            'excludeSystem' => true,
            'sectionTitle' => 'Packages with migrations',
        ]);
    }
}

