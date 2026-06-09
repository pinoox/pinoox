<?php

namespace Pinoox\Terminal\Seeder;

use Pinoox\Component\Database\Seeder\SeederToolkit;
use Pinoox\Component\Terminal;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'seeder:run',
    description: 'Run database seeders for an app',
    aliases: ['seed'],
)]

class SeederCommand extends Terminal
{
    use SelectsPackage;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Runs all seeders in database/seed/ for the selected app.

Examples:

  php pinoox seeder:run

  php pinoox seeder:run com_my_shop

  php pinoox seeder:run com_my_shop -c DemoSeeder

HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp())
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Run only one seeder class (short or full name)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Continue running even if a seeder fails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $package = $this->resolvePackageRequired($input, $output, $io, [
            'sectionTitle' => 'Run seeders for',
        ]);
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
                $this->info('Create one with: php pinoox seeder:create DemoSeeder ' . $package);
                $this->newLine();

                return Command::SUCCESS;
            }

            $this->newLine();
            $this->info('Running seeders for package: ' . $package);
            $this->newLine();

            $successCount = 0;
            $failCount = 0;

            foreach ($seeders as $seeder) {
                if ($class && $seeder['class'] !== $class) {
                    continue;
                }

                try {
                    $seederName = basename(str_replace('\\', '/', $seeder['class']));

                    $this->info('  Running: ' . $seederName . '...');
                    $seeder['instance']->run();
                    $this->success('  ✓ ' . $seederName . ' completed successfully');
                    $this->newLine();
                    $successCount++;
                } catch (\Exception $e) {
                    $this->error('  ✗ ' . ($seederName ?? 'seeder') . ' failed: ' . $e->getMessage());
                    $this->newLine();
                    $failCount++;
                    if (!$input->getOption('force')) {
                        return Command::FAILURE;
                    }
                }
            }

            $this->newLine();
            $this->info('Seeding summary:');
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

