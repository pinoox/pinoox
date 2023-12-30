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


namespace Pinoox\Terminal\Portal;

use Pinoox\Component\Helpers\PhpFile\PortalFile;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'portal:create',
    description: 'Create a new Portal class.',
)]
class CreatePortalCommand extends Terminal
{

    protected function configure(): void
    {
        $this
            ->addArgument('portalName', InputArgument::REQUIRED, 'Enter name of portal')
            ->addOption('package', 'p', InputArgument::OPTIONAL, 'change package name for example:[-p or --package=com_pinoox_welcome | --p=com_pinoox_welcome]', 'pincore')
            ->addOption('service', 's', InputArgument::OPTIONAL, 'change service name for example:[-s or --service=view | --s=view]', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $manager = new PortalFile($input);
        if($manager->create())
        {
            $this->success(sprintf('Model created in "%s"', $manager->getPortalPath()));
            $this->newLine();
            return Command::SUCCESS;
        }
        else
        {
            $this->error(sprintf('Same file exist in "%s"!', $manager->getPortalPath()));
            return Command::INVALID;
        }
    }
}