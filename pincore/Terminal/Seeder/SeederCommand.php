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

namespace Pinoox\Terminal\Seeder;

use Pinoox\Component\Database\Seeder\SeederToolkit;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'seeder:run',
    description: 'Seed the database with records',
)]

class SeederCommand extends Terminal
{
    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::OPTIONAL, 'The package to seed', $this->getDefaultPackage())
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'The class name of the root seeder')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        
        $package = $input->getArgument('package');
        $class = $input->getOption('class');

        try {
            $toolkit = new SeederToolkit();
            $toolkit->package($package)->load();

            if (!$toolkit->isSuccess()) {
                $output->writeln("<e>" . $toolkit->getErrors() . "</e>");
                return Command::FAILURE;
            }

            $seeders = $toolkit->getSeeders();
            if (empty($seeders)) {
                $output->writeln("<comment>No seeders found.</comment>");
                return Command::SUCCESS;
            }

            foreach ($seeders as $seeder) {
                if ($class && $seeder['class'] !== $class) {
                    continue;
                }

                try {
                    $seeder['instance']->run();
                    $output->writeln("<info>✓ Seeded: " . $seeder['class'] . "</info>");
                } catch (\Exception $e) {
                    $output->writeln("<e>✗ Failed to seed " . $seeder['class'] . ": " . $e->getMessage() . "</e>");
                    if (!$input->getOption('force')) {
                        return Command::FAILURE;
                    }
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<e>" . $e->getMessage() . "</e>");
            return Command::FAILURE;
        }
    }
} 