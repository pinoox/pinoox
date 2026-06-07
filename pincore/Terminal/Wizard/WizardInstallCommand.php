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

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\Pinx\PinxInstaller;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\SystemConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'wizard:install',
    description: 'Install apps or themes (delegates to pinx:install)',
)]

class WizardInstallCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Package file name or path (.pinx/.pin)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force install/update')
            ->addOption('migration', 'm', InputOption::VALUE_NONE, 'Deprecated alias; migrations run by default')
            ->addOption('skip-migrate', null, InputOption::VALUE_NONE, 'Skip database migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $packageArg = (string) $input->getArgument('package');
        $packagePath = $this->resolvePackagePath($packageArg);

        if (!is_file($packagePath)) {
            $this->error('Package file not found: "' . $packagePath . '"');
        }

        $this->warning('wizard:install is deprecated. Use pinx:install instead.');

        $installer = new PinxInstaller(
            AppEngine::___(),
            SystemConfig::path('wizard_tmp'),
        );

        $result = $installer->install($packagePath, [
            'force' => (bool) $input->getOption('force'),
            'skip_migrate' => (bool) $input->getOption('skip-migrate'),
        ]);

        if (!$result->success) {
            $io->error($result->message);
            return Command::FAILURE;
        }

        $this->success($result->message);

        return Command::SUCCESS;
    }

    private function resolvePackagePath(string $packageArg): string
    {
        $base = str_replace(['.pinx', '.pin'], '', $packageArg);

        foreach ([$packageArg, $base . '.pinx', $base . '.pin'] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $pin = Loader::getBasePath() . '/pins/' . basename($base) . '.pin';
        if (is_file($pin)) {
            return $pin;
        }

        $pinx = Loader::getBasePath() . '/pins/' . basename($base) . '.pinx';
        if (is_file($pinx)) {
            return $pinx;
        }

        return $base . '.pinx';
    }
}

