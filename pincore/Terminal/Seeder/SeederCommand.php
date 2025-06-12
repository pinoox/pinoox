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
                $this->error($toolkit->getErrors());
                return Command::FAILURE;
            }

            $seeders = $toolkit->getSeeders();
            if (empty($seeders)) {
                $this->warning('No seeders found in package: ' . $package);
                $this->info('Create a seeder using: php pinoox seeder:create YourSeederName ' . $package);
                $this->newLine();
                return Command::SUCCESS;
            }

            // Show seeding start message
            $this->newLine();
            $this->info('🌱 Running seeders for package: ' . $package);
            $this->newLine();

            $successCount = 0;
            $failCount = 0;

            foreach ($seeders as $seeder) {
                // Skip if specific class is requested and this isn't it
                if ($class && $seeder['class'] !== $class) {
                    continue;
                }

                try {
                    // Extract seeder name from full class name
                    $seederName = basename(str_replace('\\', '/', $seeder['class']));
                    
                    $this->info('  Running: ' . $seederName . '...');
                    $seeder['instance']->run();
                    $this->success('  ✓ ' . $seederName . ' completed successfully');
                    $this->newLine();
                    $successCount++;
                } catch (\Exception $e) {
                    $this->error('  ✗ ' . $seederName . ' failed: ' . $e->getMessage());
                    $this->newLine();
                    $failCount++;
                    if (!$input->getOption('force')) {
                        return Command::FAILURE;
                    }
                }
            }

            // Show summary
            $this->newLine();
            $this->info('📊 Seeding Summary:');
            $this->info('  Total seeders: ' . count($seeders));
            $this->success('  Successful: ' . $successCount);
            if ($failCount > 0) {
                $this->error('  Failed: ' . $failCount);
            }
            $this->newLine();

            return $failCount === 0 ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
} 