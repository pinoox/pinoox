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

namespace Pinoox\Terminal\Migrate;

use Pinoox\Component\Migration\MigrationToolkit;
use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'migrate:status',
    description: 'Show the status of each migration.',
)]
class MigrateStatusCommand extends Terminal
{
    use SelectsMigrationPackage;

    private string $package;

    private $mig = null;

    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::OPTIONAL, 'Enter the package name of app');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = $this->resolvePackage($input, $output, new SymfonyStyle($input, $output));

        $this->init();
        $this->status();

        return Command::SUCCESS;
    }

    private function init()
    {
        $this->mig = new  MigrationToolkit();
        $this->mig->package($this->package)
            ->action('status')
            ->load();

        if (!$this->mig->isSuccess()) {
            $this->error($this->mig->getErrors());
        }
    }

    private function status()
    {
        $migrations = $this->mig->getMigrations();

        if (empty($migrations)) {
            $this->success('There are no migrations!');
            $this->stop();
        }

        $this->success('Migration status for app: ');
        $this->info($this->package);
        $this->newLine();
        $this->table(['App', 'Migration', 'Batch', 'Status', 'Created at'], $this->getRows($migrations));
    }

    private function getRows($migrations): array
    {
        $rows = [];
        foreach ($migrations as $m) {
            $status = !empty($m['sync']) ? 'Done' : 'Pending';
            $batch = $m['sync']['batch'] ?? null;
            $rows[] = [
                $this->package,
                $m['fileName'],
                $batch,
                $status,
                $this->createdAt($m['fileName'], $m['migrationFile'] ?? null),
            ];
        }
        return $rows;
    }

    private function createdAt(string $fileName, ?string $file): string
    {
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})_/', $fileName, $matches)) {
            return sprintf(
                '%s-%s-%s %s:%s:%s',
                $matches[1],
                $matches[2],
                $matches[3],
                $matches[4],
                $matches[5],
                $matches[6]
            );
        }

        return is_string($file) && is_file($file) ? date('Y-m-d H:i:s', filemtime($file)) : '-';
    }

}
