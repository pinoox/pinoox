<?php

namespace Pinoox\Terminal\Concerns;

use Pinoox\Portal\App\AppEngine;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interactive theme folder selection for app themes under theme/{name}/.
 */
trait SelectsTheme
{
    /**
     * @param array<string, string> $candidates
     */
    protected function resolveThemeChoice(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        string $package,
        array $candidates,
        array $config = [],
    ): string {
        $default = (string) ($config['default'] ?? AppEngine::config($package)->get('theme', 'default'));
        $sectionTitle = (string) ($config['sectionTitle'] ?? 'Available themes');
        $emptyMessage = (string) ($config['emptyMessage'] ?? 'No theme folders were found for package: ' . $package);
        $invalidMessage = (string) ($config['invalidMessage'] ?? "Theme '%s' was not found.");

        $theme = $this->readThemeInput($input);

        if ($theme !== '') {
            if (!isset($candidates[$theme])) {
                throw new \InvalidArgumentException(sprintf($invalidMessage, $theme));
            }

            return $theme;
        }

        if ($candidates === []) {
            throw new \RuntimeException($emptyMessage);
        }

        if (count($candidates) === 1) {
            $only = array_key_first($candidates);
            $io->note('Using the only available theme: ' . $only);

            return $only;
        }

        if (!$input->isInteractive()) {
            if (isset($candidates[$default])) {
                return $default;
            }

            throw new \RuntimeException('Theme is required in non-interactive mode. Use --theme=name.');
        }

        $io->section($sectionTitle);
        $io->table(['#', 'Theme', 'Details'], $this->themeRows($candidates));

        $prompt = sprintf('Select theme [%s]: ', $default);
        $question = new Question($prompt, $default);
        $question->setAutocompleterValues(array_keys($candidates));
        $question->setValidator(function ($answer) use ($candidates, $invalidMessage) {
            $answer = trim((string) $answer);
            $theme = $this->resolveThemeAnswer($answer, $candidates);

            if ($theme === null) {
                throw new \RuntimeException(sprintf($invalidMessage, $answer));
            }

            return $theme;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    protected function readThemeInput(InputInterface $input): string
    {
        if (!$input->hasOption('theme')) {
            return '';
        }

        $value = $input->getOption('theme');
        if ($value === null || $value === false) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * @param array<string, string> $themes
     * @return list<array<int, string>>
     */
    protected function themeRows(array $themes): array
    {
        $rows = [];

        foreach ($themes as $theme => $details) {
            $rows[] = [count($rows), $theme, $details];
        }

        return $rows;
    }

    /**
     * @param array<string, string> $themes
     */
    protected function resolveThemeAnswer(string $answer, array $themes): ?string
    {
        if (isset($themes[$answer])) {
            return $answer;
        }

        if (ctype_digit($answer)) {
            $keys = array_keys($themes);

            return $keys[(int) $answer] ?? null;
        }

        return null;
    }
}
