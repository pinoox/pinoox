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

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Terminal;
use Pinoox\Portal\StubGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'seeder:create',
    description: 'Create a new seeder class',
)]
class SeederCreateCommand extends Terminal
{
    private string $package;
    private string $seeder;

    protected function configure(): void
    {
        $this
            ->addArgument('seeder', InputArgument::REQUIRED, 'The name of the seeder')
            ->addArgument('package', InputArgument::OPTIONAL, 'The package to create the seeder in', $this->getDefaultPackage());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = $input->getArgument('package');
        $this->seeder = $input->getArgument('seeder');

        try {
            $isCreated = $this->create();

            if ($isCreated) {
                $this->newLine();
                $this->success('🌱 SEEDER CREATED SUCCESSFULLY');
                $this->newLine();
                $this->info('  Name:      ' . $this->getSeederClassName());
                $this->info('  Location:  ' . path('~apps') . '/' . $this->package . '/Database/Seeders');
                $this->info('  Package:   ' . $this->package);
                $this->newLine();
                $this->warning('  Run the seeder using: php pinoox seeder:run ' . $this->package . ' -c ' . $this->getSeederClassName());
                $this->newLine();
                return Command::SUCCESS;
            }

            $this->error('❌ Failed to generate seeder class!');
            return Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('❌ ' . $e->getMessage());
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
        if ($this->package === 'pincore') {
            return path('~pincore') . '/Database/seeders';
        }
        return path('~apps') . '/' . $this->package . '/Database/Seeders';
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
        return $this->package === 'pincore' 
            ? 'Pinoox\\Database\\seeders' 
            : 'App\\' . $this->package . '\\Database\\Seeders';
    }
} 