<?php

namespace Pinoox\Terminal\Migrate;

use Pinoox\Portal\App\AppEngine;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

trait SelectsMigrationPackage
{
    protected function resolvePackage(InputInterface $input, OutputInterface $output, SymfonyStyle $io): string
    {
        $package = $this->normalizePackageInput((string)$input->getArgument('package'));

        if (!empty($package)) {
            if (!$this->packageExists($package)) {
                throw new \InvalidArgumentException("Package '{$package}' was not found.");
            }

            return $package;
        }

        $packages = $this->packages();
        $io->section('Available packages');
        $io->table(['#', 'Package', 'Name'], $this->packageRows($packages));

        $question = new Question('Select package [pincore]: ', 'pincore');
        $question->setAutocompleterValues(array_keys($packages));
        $question->setValidator(function ($answer) use ($packages) {
            $answer = $this->normalizePackageInput((string)$answer);
            $package = $this->resolvePackageAnswer($answer, $packages);

            if ($package === null) {
                throw new \RuntimeException("Package '{$answer}' was not found.");
            }

            return $package;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    protected function packages(): array
    {
        $packages = [
            'pincore' => 'Core platform',
        ];

        foreach (AppEngine::all() as $package => $manager) {
            $name = $manager->config()->get('name') ?: $package;
            $packages[$package] = $name;
        }

        return $packages;
    }

    protected function packageRows(array $packages): array
    {
        $rows = [];

        foreach ($packages as $package => $name) {
            $rows[] = [count($rows), $package, $name];
        }

        return $rows;
    }

    protected function resolvePackageAnswer(string $answer, array $packages): ?string
    {
        if (isset($packages[$answer])) {
            return $answer;
        }

        if (ctype_digit($answer)) {
            $keys = array_keys($packages);
            $index = (int)$answer;

            return $keys[$index] ?? null;
        }

        return null;
    }

    protected function packageExists(string $package): bool
    {
        return $package === 'pincore' || AppEngine::exists($package);
    }

    protected function normalizePackageInput(string $package): string
    {
        $package = preg_replace('/^\xEF\xBB\xBF/', '', $package);
        $package = preg_replace('/^[^A-Za-z0-9_]+/', '', $package);

        return trim($package);
    }
}
