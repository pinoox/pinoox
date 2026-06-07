<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Terminal\Wizard;

use Pinoox\Component\Package\Pinx\PinxBuilder;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'wizard:export',
    description: 'Export production package (delegates to pinx:build)',
)]

class WizardExportCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Enter package name')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Export format (pinx, pin, zip)', 'pinx')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $format = strtolower((string) $input->getOption('format'));
        if ($format !== 'pinx') {
            $this->warning('Legacy formats pin/zip are deprecated. Building .pinx instead.');
        }

        $package = $input->getArgument('package');
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('yes') && !$io->confirm('Build .pinx package for "' . $package . '"?', true)) {
            $this->warning('Export canceled.');
            return Command::SUCCESS;
        }

        try {
            $result = (new PinxBuilder(AppEngine::___()))->build($package);
            $this->success('Pinx export completed: ' . $result['path']);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}

