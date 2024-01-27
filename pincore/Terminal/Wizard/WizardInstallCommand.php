<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Terminal\Wizard;

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Terminal;
use Pinoox\Portal\AppWizard;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'wizard:install',
    description: 'Install apps or templates',
)]
class WizardInstallCommand extends Terminal
{
    const PATH = 'pins/';

    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Enter package name')
            ->addOption('force', 'f', null, 'if the package is already installed, you can force the installation. example:[wizard [package_name] -f]')
            ->addOption('migration', 'm', null, 'migrate database tables. example:[wizard [package_name] -m]');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

      
        $package = $input->getArgument('package');
        $force = $input->getOption('force')  ?? null;
        $migration = $input->getOption('migration')  ?? null;

        $package = str_replace('.pin', '', $package);
        $pin = Loader::getBasePath() .'/'. $package . '.pin';

        if (!file_exists($pin)) {
            $this->error('package file not found: "' . $pin . '"');
        }

        $wizard = AppWizard::open($pin);

        $wizard->force($force);
        $wizard->migration($migration);

        if ($wizard->isInstalled() && !$force) {
            // Continue installation
            $confirm = $this->confirm('The package already installed, Do you want to continue installation? (yes/no) ', $input, $output);
            if ($confirm) {
                $wizard->force();
            } else {
                $this->error('Installation canceled.');
            }
        }

        $result = $wizard->install();
        $this->success($result['message'] . ': "' . $package.'"');

        return Command::SUCCESS;
    }

}