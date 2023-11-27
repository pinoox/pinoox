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


namespace pinoox\terminal\portal;

use pinoox\component\Helpers\PhpFile\PortalFile;
use pinoox\component\source\PortalManager;
use pinoox\component\Terminal;
use pinoox\portal\AppManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'portal:update',
    description: 'Update an exists portal class.',
)]
class UpdatePortalCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->addArgument('portalName', InputArgument::REQUIRED, 'Enter name of portal')
            ->addOption('package', 'p', InputArgument::OPTIONAL, 'change package name for example:[-p or --package=com_pinoox_welcome | --p=com_pinoox_welcome]', 'pincore');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $manager = new PortalFile($input, false);
        if ($manager->update()) {
            $this->success(sprintf('Portal update in "%s".', $manager->getPortalPath()));
            $this->newLine();
            return Command::SUCCESS;
        } else {
            $this->error(sprintf('err "%s"!', $manager->getPortalPath()));
            return Command::INVALID;
        }
    }
}