<?php

namespace Pinoox\Terminal\Seeder;

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\StubGenerator;
use Pinoox\Support\SystemConfig;
use Pinoox\Terminal\Concerns\SelectsPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'seeder:create',
    description: 'Create a new database seeder class in an app',
)]

class SeederCreateCommand extends Terminal
{
    use SelectsPackage;

    private string $package;
    private string $seeder;

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Creates a seeder stub inside database/seed/ for the selected app.

Example:

  php pinoox seeder:create DemoSeeder com_my_shop

HELP
            )
            ->addArgument('seeder', InputArgument::REQUIRED, 'Seeder name (e.g. DemoSeeder or Demo)')
            ->addArgument('package', InputArgument::OPTIONAL, $this->packageArgumentHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $io = new SymfonyStyle($input, $output);
        $this->package = $this->resolvePackageRequired($input, $output, $io, [
            'sectionTitle' => 'Create seeder in',
        ]);
        $this->seeder = $input->getArgument('seeder');

        try {
            $isCreated = $this->create();

            if ($isCreated) {
                $this->newLine();
                $this->success('Seeder created successfully');
                $this->newLine();
                $this->info('  Name:      ' . $this->getSeederClassName());
                $this->info('  Location:  ' . $this->getSeederPath());
                $this->info('  Package:   ' . $this->package);
                $this->newLine();
                $this->warning('  Run it with: php pinoox seeder:run ' . $this->package . ' -c ' . $this->getSeederClassName());
                $this->newLine();

                return Command::SUCCESS;
            }

            $this->error('Failed to generate seeder class!');

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function create()
    {
        return StubGenerator::generate('seeder.create.stub', $this->getExportPath(), [
            'copyright' => StubGenerator::get('copyright.stub'),
            'namespace' => $this->getNamespace(),
            'package' => $this->package,
        ]);
    }

    private function getExportPath(): string
    {
        $seederPath = $this->getSeederPath();
        $this->ensureSeederDirectoryExists($seederPath);

        $className = $this->getSeederClassName();

        return $seederPath . '/' . $className . '.php';
    }

    private function getSeederPath(): string
    {
        if ($this->package === 'platform') {
            return SystemConfig::platformPath('seed');
        }

        return AppEngine::path($this->package) . '/' . trim(SystemConfig::rawPath('app_seed', 'database/seed'), '/\\');
    }

    private function ensureSeederDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function getSeederClassName(): string
    {
        $name = Str::toCamelCase($this->seeder);

        return ucfirst($name) . 'Seeder';
    }

    private function getNamespace(): string
    {
        return $this->package === 'platform'
            ? 'Pinoox\\Database\\seed'
            : 'App\\' . $this->package . '\\database\\seed';
    }
}

