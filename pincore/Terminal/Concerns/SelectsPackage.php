<?php

namespace Pinoox\Terminal\Concerns;

use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\SystemApp;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interactive package selection for Pinoox CLI commands.
 *
 * Type a package name (e.g. com_my_shop), its row number, or press Enter for the default.
 */
trait SelectsPackage
{
    protected function packageArgumentHelp(bool $allowAll = false, bool $optional = false): string
    {
        if ($allowAll) {
            return 'App package, platform, or all. Leave empty to pick from the list.';
        }

        if ($optional) {
            return 'Filter by app package. Leave empty to show all apps or pick from the list.';
        }

        return 'App package name (e.g. com_my_shop) or platform. Leave empty to pick from the list.';
    }

    protected function resolvePackageRequired(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        array $config = [],
    ): string {
        $package = $this->resolvePackageChoice($input, $output, $io, $config);

        return $package ?? ($config['default'] ?? 'platform');
    }

    /**
     * @return string|null null means "all packages" when optional filter is used
     */
    protected function resolvePackageFilter(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        array $config = [],
    ): ?string {
        $config['optional'] = true;
        $config['allowAll'] = true;
        $config['default'] = $config['default'] ?? 'all';

        $package = $this->resolvePackageChoice($input, $output, $io, $config);

        return $package === 'all' ? null : $package;
    }

    protected function resolvePackageChoice(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        array $config = [],
    ): ?string {
        $allowAll = (bool) ($config['allowAll'] ?? false);
        $optional = (bool) ($config['optional'] ?? false);
        $excludeSystem = (bool) ($config['excludeSystem'] ?? false);
        $default = (string) ($config['default'] ?? ($allowAll ? 'all' : 'platform'));
        $argument = (string) ($config['argument'] ?? 'package');
        $optionNames = $config['options'] ?? ['package', 'app'];
        $sectionTitle = (string) ($config['sectionTitle'] ?? 'Available packages');
        $includeTestColumn = (bool) ($config['includeTestColumn'] ?? false);
        $appsOnly = (bool) ($config['appsOnly'] ?? false);

        $package = $this->readPackageInput($input, $argument, $optionNames);

        if ($package === 'all' && $allowAll) {
            return 'all';
        }

        if ($package !== '') {
            if (!$this->packageExists($package, $excludeSystem, $allowAll, $appsOnly)) {
                throw new \InvalidArgumentException(sprintf("Package '%s' was not found.", $package));
            }

            return $package;
        }

        if ($optional && !$input->isInteractive()) {
            return null;
        }

        if (!$input->isInteractive()) {
            return $default === 'all' && $allowAll ? 'all' : $default;
        }

        $packages = $this->packageChoices($excludeSystem, $appsOnly);

        if ($allowAll) {
            $packages = ['all' => 'All packages'] + $packages;
        }

        $io->section($sectionTitle);
        $io->table(
            $this->packageTableHeaders($includeTestColumn),
            $this->packageRows($packages, $includeTestColumn),
        );

        $selectionHint = trim((string) ($config['selectionHint'] ?? ''));
        if ($selectionHint !== '') {
            $io->newLine();
            $io->writeln('  ' . $selectionHint);
            $io->newLine();
        }

        $prompt = sprintf('Select package [%s]: ', $default);
        $question = new Question($prompt, $default);
        $question->setAutocompleterValues(array_keys($packages));
        $question->setValidator(function ($answer) use ($packages, $allowAll) {
            $answer = $this->normalizePackageInput((string) $answer);
            $package = $this->resolvePackageAnswer($answer, $packages);

            if ($package === null) {
                throw new \RuntimeException(sprintf("Package '%s' was not found.", $answer));
            }

            if ($package === 'all' && !$allowAll) {
                throw new \RuntimeException("Package 'all' is not valid for this command.");
            }

            return $package;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    /**
     * @param list<string> $optionNames
     */
    protected function readPackageInput(InputInterface $input, string $argument, array $optionNames): string
    {
        if ($input->hasArgument($argument)) {
            $value = $this->normalizePackageInput((string) $input->getArgument($argument));
            if ($value !== '') {
                return $value;
            }
        }

        foreach ($optionNames as $option) {
            if (!$input->hasOption($option)) {
                continue;
            }

            $value = $input->getOption($option);
            if ($value === null || $value === false) {
                continue;
            }

            $value = $this->normalizePackageInput((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return array<string, string>
     */
    protected function packageChoices(bool $excludeSystem = false, bool $appsOnly = false): array
    {
        $packages = $appsOnly ? [] : ['platform' => 'Core platform'];

        foreach (AppEngine::all() as $package => $manager) {
            if ($excludeSystem && in_array($package, [SystemApp::PACKAGE, SystemApp::LEGACY_PACKAGE], true)) {
                continue;
            }

            $packages[$package] = (string) ($manager->config()->get('name') ?: $package);
        }

        return $packages;
    }

    /**
     * @return list<string>
     */
    protected function packageTableHeaders(bool $includeTestColumn = false): array
    {
        return $includeTestColumn
            ? ['#', 'Package', 'Name', 'Tests']
            : ['#', 'Package', 'Name'];
    }

    /**
     * @param array<string, string> $packages
     * @return list<array<int, string>>
     */
    protected function packageRows(array $packages, bool $includeTestColumn = false): array
    {
        $rows = [];

        foreach ($packages as $package => $name) {
            $row = [count($rows), $package, $name];

            if ($includeTestColumn) {
                $row[] = method_exists($this, 'testPath') && is_dir($this->testPath($package)) ? 'yes' : 'no';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<string, string> $packages
     */
    protected function resolvePackageAnswer(string $answer, array $packages): ?string
    {
        if (isset($packages[$answer])) {
            return $answer;
        }

        if (ctype_digit($answer)) {
            $keys = array_keys($packages);

            return $keys[(int) $answer] ?? null;
        }

        return null;
    }

    protected function packageExists(
        string $package,
        bool $excludeSystem = false,
        bool $allowAll = false,
        bool $appsOnly = false,
    ): bool {
        if ($allowAll && $package === 'all') {
            return true;
        }

        if ($appsOnly && $package === 'platform') {
            return false;
        }

        if ($package === 'platform') {
            return true;
        }

        if ($excludeSystem && in_array($package, [SystemApp::PACKAGE, SystemApp::LEGACY_PACKAGE], true)) {
            return false;
        }

        return AppEngine::exists($package);
    }

    protected function normalizePackageInput(string $package): string
    {
        $package = preg_replace('/^\xEF\xBB\xBF/', '', $package);
        $package = preg_replace('/^[^A-Za-z0-9_]+/', '', $package);

        return trim($package);
    }

    protected function bindPackageOption(InputInterface $input, string $package): void
    {
        if ($input instanceof \Symfony\Component\Console\Input\Input) {
            $input->setOption('package', $package);
        }
    }

    /**
     * Pick from a filtered candidate list (e.g. apps with API routes only).
     *
     * @param array<string, string> $candidates
     */
    protected function resolvePackageFromCandidates(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        array $candidates,
        array $config = [],
    ): string {
        $argument = (string) ($config['argument'] ?? 'package');
        $optionNames = $config['options'] ?? ['package', 'app'];
        $sectionTitle = (string) ($config['sectionTitle'] ?? 'Select package');
        $emptyMessage = (string) ($config['emptyMessage'] ?? 'No matching packages found.');
        $invalidMessage = (string) ($config['invalidMessage'] ?? "Package '%s' was not found.");

        $package = $this->readPackageInput($input, $argument, $optionNames);

        if ($package !== '') {
            if (!isset($candidates[$package])) {
                throw new \InvalidArgumentException(sprintf($invalidMessage, $package));
            }

            return $package;
        }

        if ($candidates === []) {
            throw new \RuntimeException($emptyMessage);
        }

        if (count($candidates) === 1) {
            $only = array_key_first($candidates);
            $io->note('Using the only available package: ' . $only);

            return $only;
        }

        if (!$input->isInteractive()) {
            throw new \RuntimeException('Package is required in non-interactive mode.');
        }

        $io->section($sectionTitle);
        $io->table(['#', 'Package', 'Name'], $this->packageRows($candidates));

        $question = new Question('Select package: ');
        $question->setAutocompleterValues(array_keys($candidates));
        $question->setValidator(function ($answer) use ($candidates, $invalidMessage) {
            $answer = $this->normalizePackageInput((string) $answer);
            $package = $this->resolvePackageAnswer($answer, $candidates);

            if ($package === null) {
                throw new \RuntimeException(sprintf($invalidMessage, $answer));
            }

            return $package;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }
}

