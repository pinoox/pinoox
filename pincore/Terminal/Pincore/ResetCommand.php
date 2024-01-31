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

namespace Pinoox\Terminal\Pincore;

use Pinoox\Component\Dir;
use Pinoox\Component\File;
use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\FileSystem;
use Pinoox\Portal\Pinker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


// the "name" and "description" arguments of AsCommand replace the
// static $defaultName and $defaultDescription properties
#[AsCommand(
    name: 'reset',
    description: 'Reset to factory.',
)]
class ResetCommand extends Terminal
{

    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::OPTIONAL, 'Enter the package name that you want to reset to factory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $package = $input->getArgument('package');

        $package = (!empty($package) && AppEngine::exists($package)) ? $package : 'pincore';
        $pinkerDir = path('~:' . $package . '/pinker');
        FileSystem::remove($pinkerDir);

        self::success('Pinker directory [' . $package . '] removed successfully.');

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function printMessages($messages): void
    {
        if (empty($messages)) throw new Exception($messages);

        foreach ($messages as $message) {
            $this->success($message);
        }
    }

}